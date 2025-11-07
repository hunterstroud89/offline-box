<?php
/**
 * File Search API for OfflineBox
 * Handles file searches within the local file system
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

class FileSearchAPI {
    
    private $searchPaths;
    private $maxResults;
    
    public function __construct() {
        // Define search paths - only search accessible directories
        // Auto-detect environment (Pi vs development)
        $webRoot = $this->isRaspberryPi() ? '/var/www/html' : '/Volumes/html';
        
        $this->searchPaths = [
            '/media/hunter/OFFLINEBOX', // External drive files
            $webRoot, // Local web directory files
        ];
        $this->maxResults = 50;
    }
    
    /**
     * Detect if running on Raspberry Pi
     */
    private function isRaspberryPi() {
        return file_exists('/etc/rpi-issue') || 
               (php_uname('m') === 'armv7l') || 
               (php_uname('m') === 'aarch64') ||
               is_dir('/var/www/html');
    }
    
    /**
     * Handle file search requests
     */
    public function handleSearch() {
        // Parse query string if running from command line
        if (php_sapi_name() === 'cli' && isset($_SERVER['QUERY_STRING'])) {
            parse_str($_SERVER['QUERY_STRING'], $_GET);
        }
        
        $query = $_GET['q'] ?? '';
        $type = $_GET['type'] ?? 'files';
        $limit = min((int)($_GET['limit'] ?? 20), $this->maxResults);
        
        if (empty($query) || strlen($query) < 2) {
            $this->sendResponse(['results' => []]);
            return;
        }
        
        switch ($type) {
            case 'files':
                $results = $this->searchFiles($query, $limit);
                break;
            case 'kiwix':
                $results = $this->searchKiwix($query, $limit);
                break;
            case 'all':
                // Search both files and Kiwix
                $fileResults = $this->searchFiles($query, $limit / 2);
                $kiwixResults = $this->searchKiwix($query, $limit / 2);
                $results = array_merge($fileResults, $kiwixResults);
                // Sort combined results by relevance
                usort($results, function($a, $b) use ($query) {
                    return $this->compareRelevance($a, $b, $query);
                });
                $results = array_slice($results, 0, $limit);
                break;
            default:
                $results = [];
        }
        
        $this->sendResponse(['results' => $results]);
    }
    
    /**
     * Search for files in configured paths
     */
    private function searchFiles($query, $limit) {
        $results = [];
        $query_lower = strtolower($query);
        $found = 0;
        
        foreach ($this->searchPaths as $path) {
            if (!is_dir($path) || !is_readable($path)) {
                continue;
            }
            
            $results = array_merge($results, $this->searchInDirectory($path, $query_lower, $limit - $found));
            $found = count($results);
            
            if ($found >= $limit) {
                break;
            }
        }
        
        // Sort results by relevance (exact matches first, then partial matches)
        usort($results, function($a, $b) use ($query_lower) {
            $aName = strtolower($a['name']);
            $bName = strtolower($b['name']);
            
            // Exact matches first
            $aExact = ($aName === $query_lower) ? 1 : 0;
            $bExact = ($bName === $query_lower) ? 1 : 0;
            if ($aExact !== $bExact) return $bExact - $aExact;
            
            // Then by how early the match appears in the filename
            $aPos = strpos($aName, $query_lower);
            $bPos = strpos($bName, $query_lower);
            if ($aPos !== $bPos) return $aPos - $bPos;
            
            // Finally alphabetically
            return strcmp($aName, $bName);
        });
        
        return array_slice($results, 0, $limit);
    }
    
    /**
     * Search Kiwix content (ZIM files/books)
     * Since API search may not be available, we'll create a basic suggestion system
     */
    private function searchKiwix($query, $limit) {
        $results = [];
        
        // Check if Kiwix server is running
        $kiwixUrl = 'http://offlinebox.local:8082';
        
        // Dynamically get available books from Kiwix server
        $availableBooks = $this->getKiwixBooks($kiwixUrl);
        
        if (empty($availableBooks)) {
            // Fallback to a basic check if API fails
            error_log("FileSearchAPI: Could not get Kiwix books, using fallback");
            return $results;
        }
        
        try {
            foreach ($availableBooks as $book) {
                if (count($results) >= $limit) break;
                
                $bookId = $book['id'];
                $bookName = $book['name'];
                
                // Use Kiwix's built-in search for each book
                $searchResults = $this->searchKiwixBook($kiwixUrl, $bookId, $query);
                
                // Track URLs to prevent duplicates
                $seenUrls = [];
                
                foreach ($searchResults as $result) {
                    if (count($results) >= $limit) break;
                    
                    $url = $kiwixUrl . '/' . $bookId . '/' . $result['path'];
                    
                    // Skip if we've already added this URL
                    if (in_array($url, $seenUrls)) {
                        continue;
                    }
                    
                    $seenUrls[] = $url;
                    
                    $results[] = [
                        'name' => $result['title'],
                        'type' => 'kiwix',
                        'source' => $bookName,
                        'book' => $bookId,
                        'snippet' => $result['snippet'],
                        'url' => $url,
                        'modified' => date('Y-m-d'),
                        'folder' => $book['type'],
                        'language' => $book['language'],
                        'book_description' => $book['description']
                    ];
                }
            }
            
        } catch (Exception $e) {
            error_log("FileSearchAPI: Kiwix search error: " . $e->getMessage());
        }
        
        return $results;
    }

    /**
     * Search within a specific Kiwix book using direct URL testing
     */
    private function searchKiwixBook($kiwixUrl, $bookId, $query) {
        $results = [];
        $testedUrls = [];
        
        try {
            // Generate likely article titles based on the query
            $searchTerms = $this->generateSearchTerms($query);
            
            foreach ($searchTerms as $term) {
                if (count($results) >= 2) break; // Limit results per book to reduce duplicates
                
                $testUrl = $kiwixUrl . '/' . $bookId . '/' . $term['path'];
                
                // Skip if we already tested this exact URL
                if (in_array($testUrl, $testedUrls)) {
                    continue;
                }
                
                $testedUrls[] = $testUrl;
                
                $context = stream_context_create([
                    'http' => [
                        'timeout' => 3,
                        'method' => 'HEAD',
                        'ignore_errors' => true,
                        'follow_location' => false  // Don't follow redirects to detect them
                    ]
                ]);
                
                $headers = @get_headers($testUrl, 1, $context);
                
                // If the page exists (200 OK or 302 redirect)
                if ($headers && (strpos($headers[0], '200') !== false || strpos($headers[0], '302') !== false)) {
                    
                    // For redirects, we need to determine the actual target
                    $finalUrl = $testUrl;
                    $isRedirect = false;
                    
                    if (strpos($headers[0], '302') !== false) {
                        $isRedirect = true;
                        // Follow the redirect to get the actual content URL
                        $redirectContext = stream_context_create([
                            'http' => [
                                'timeout' => 3,
                                'method' => 'HEAD',
                                'ignore_errors' => true,
                                'follow_location' => true,
                                'max_redirects' => 3
                            ]
                        ]);
                        
                        $finalHeaders = @get_headers($testUrl, 1, $redirectContext);
                        if ($finalHeaders && isset($finalHeaders['Location'])) {
                            $location = is_array($finalHeaders['Location']) ? end($finalHeaders['Location']) : $finalHeaders['Location'];
                            if (strpos($location, 'http') === 0) {
                                $finalUrl = $location;
                            } else {
                                $finalUrl = $kiwixUrl . '/' . $bookId . '/' . ltrim($location, '/');
                            }
                        }
                    }
                    
                    // Check if we already have a result that leads to the same final URL or content
                    $isDuplicate = false;
                    foreach ($results as $existingIndex => $existingResult) {
                        // Check if final URLs are the same or if both URLs point to the same content
                        $sameContent = ($existingResult['final_url'] === $finalUrl) || 
                                      ($existingResult['path'] === $term['path']);
                        
                        if ($sameContent) {
                            // If this is a direct hit (200) and the existing is a redirect, replace it
                            if (!$isRedirect && $existingResult['is_redirect']) {
                                unset($results[$existingIndex]);
                                $results = array_values($results); // Re-index array
                                $isDuplicate = false; // Allow this better result
                                break;
                            } else {
                                $isDuplicate = true;
                                break;
                            }
                        }
                    }
                    
                    if (!$isDuplicate) {
                        $results[] = [
                            'title' => $term['title'],
                            'path' => $term['path'],
                            'snippet' => $term['snippet'],
                            'final_url' => $finalUrl,
                            'is_redirect' => $isRedirect
                        ];
                    }
                }
            }
        } catch (Exception $e) {
            error_log("FileSearchAPI: Error searching book $bookId: " . $e->getMessage());
        }
        
        return $results;
    }

    /**
     * Generate search terms based on query - prioritized and limited to reduce duplicates
     */
    private function generateSearchTerms($query) {
        $terms = [];
        $query = trim($query);
        
        if (empty($query)) return $terms;
        
        // Base format - most common
        $baseFormat = str_replace(' ', '_', ucwords(strtolower($query)));
        
        // Add the base format first
        $terms[] = [
            'path' => $baseFormat,
            'title' => str_replace('_', ' ', $baseFormat),
            'snippet' => "Article about " . str_replace('_', ' ', strtolower($baseFormat))
        ];
        
        // Add only the most common disambiguation - prioritize (film) over (movie)
        $priorityDisambiguations = ['_(film)', '_(character)', '_(band)', '_(album)', '_(book)'];
        
        foreach ($priorityDisambiguations as $disambig) {
            $terms[] = [
                'path' => $baseFormat . $disambig,
                'title' => str_replace('_', ' ', $baseFormat . str_replace('_', ' ', $disambig)),
                'snippet' => "Article about " . str_replace('_', ' ', strtolower($baseFormat)) . str_replace(['_(', ')'], [' (', ')'], $disambig)
            ];
        }
        
        // Add a few alternative formats only if the base format doesn't work
        $altFormats = [
            str_replace(' ', '_', $query),
            ucfirst(strtolower(str_replace(' ', '_', $query))),
        ];
        
        foreach ($altFormats as $format) {
            if ($format !== $baseFormat) {
                $terms[] = [
                    'path' => $format,
                    'title' => str_replace('_', ' ', $format),
                    'snippet' => "Article about " . str_replace('_', ' ', strtolower($format))
                ];
            }
        }
        
        return $terms;
    }
    
    /**
     * Generate Wikipedia article suggestions based on query
     */
    private function generateWikipediaSuggestions($query) {
        $suggestions = [];
        $query = trim($query);
        
        if (empty($query)) return $suggestions;
        
        // Common Wikipedia article title formats
        $formats = [
            // Exact match
            str_replace(' ', '_', ucwords(strtolower($query))),
            
            // Title case
            str_replace(' ', '_', ucfirst(strtolower($query))),
            
            // All lowercase with underscores
            str_replace(' ', '_', strtolower($query)),
            
            // For movies/TV shows
            str_replace(' ', '_', ucwords(strtolower($query))) . '_(film)',
            str_replace(' ', '_', ucwords(strtolower($query))) . '_(movie)',
            str_replace(' ', '_', ucwords(strtolower($query))) . '_(TV_series)',
            
            // For people
            str_replace(' ', '_', ucwords(strtolower($query))) . '_(person)',
            str_replace(' ', '_', ucwords(strtolower($query))) . '_(actor)',
            
            // For bands/music
            str_replace(' ', '_', ucwords(strtolower($query))) . '_(band)',
            
            // For books
            str_replace(' ', '_', ucwords(strtolower($query))) . '_(book)',
            str_replace(' ', '_', ucwords(strtolower($query))) . '_(novel)'
        ];
        
        foreach ($formats as $format) {
            $title = str_replace('_', ' ', $format);
            $suggestions[] = [
                'path' => $format,
                'title' => $title,
                'description' => "Wikipedia article about {$title}"
            ];
        }
        
        return $suggestions;
    }
    
    /**
     * Compare relevance for mixed search results
     */
    private function compareRelevance($a, $b, $query) {
        $query_lower = strtolower($query);
        $aName = strtolower($a['name']);
        $bName = strtolower($b['name']);
        
        // Exact matches first
        $aExact = ($aName === $query_lower) ? 1 : 0;
        $bExact = ($bName === $query_lower) ? 1 : 0;
        if ($aExact !== $bExact) return $bExact - $aExact;
        
        // Then by how early the match appears
        $aPos = strpos($aName, $query_lower);
        $bPos = strpos($bName, $query_lower);
        if ($aPos !== $bPos) return $aPos - $bPos;
        
        // Finally alphabetically
        return strcmp($aName, $bName);
    }
    
    /**
     * Recursively search in a directory
     */
    private function searchInDirectory($dir, $query, $remainingLimit) {
        $results = [];
        
        if ($remainingLimit <= 0) {
            return $results;
        }
        
        try {
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS),
                RecursiveIteratorIterator::LEAVES_ONLY
            );
            
            foreach ($iterator as $file) {
                if (count($results) >= $remainingLimit) {
                    break;
                }
                
                $filename = $file->getFilename();
                $filename_lower = strtolower($filename);
                
                // Skip hidden files and system files
                if ($filename[0] === '.' || strpos($filename, '.DS_Store') !== false) {
                    continue;
                }
                
                // Check if filename contains the search query
                if (strpos($filename_lower, $query) !== false) {
                    $relativePath = $this->getRelativePath($file->getPathname());
                    $fileInfo = $this->getFileInfo($file);
                    $folderPath = dirname($relativePath);
                    $folderName = $folderPath === '.' ? 'Root' : basename($folderPath);
                    
                    $fileResult = [
                        'name' => $filename,
                        'path' => $relativePath,
                        'fullPath' => $file->getPathname(),
                        'folder' => $folderName,
                        'folderPath' => $folderPath,
                        'size' => $fileInfo['size'],
                        'sizeFormatted' => $fileInfo['sizeFormatted'],
                        'type' => $fileInfo['type'],
                        'extension' => $fileInfo['extension'],
                        'modified' => $fileInfo['modified'],
                        'isImage' => $fileInfo['isImage'],
                        'isVideo' => $fileInfo['isVideo']
                    ];
                    
                    // Add the URL after we have the full result array
                    $fileResult['url'] = $this->generateFileUrl($relativePath, $fileResult);
                    
                    $results[] = $fileResult;
                }
            }
        } catch (Exception $e) {
            error_log("FileSearchAPI: Error searching directory $dir: " . $e->getMessage());
        }
        
        return $results;
    }
    
    /**
     * Get relative path from full path
     */
    private function getRelativePath($fullPath) {
        // Convert full path to relative path from files root
        foreach ($this->searchPaths as $searchPath) {
            if (strpos($fullPath, $searchPath) === 0) {
                return substr($fullPath, strlen($searchPath) + 1);
            }
        }
        return $fullPath;
    }
    
    /**
     * Get file information
     */
    private function getFileInfo($file) {
        $ext = strtolower($file->getExtension());
        $modified = date('Y-m-d', $file->getMTime());
        
        // Image extensions
        $imageExts = ['png', 'jpg', 'jpeg', 'gif', 'webp', 'bmp', 'svg', 'tif', 'tiff'];
        $isImage = in_array($ext, $imageExts);
        
        // Video extensions
        $videoExts = ['mp4', 'm4v', 'mov', 'webm', 'ogg', 'ogv', 'mkv', 'avi'];
        $isVideo = in_array($ext, $videoExts);
        
        // File type categorization
        if ($isImage) {
            $type = 'image';
        } elseif ($isVideo) {
            $type = 'video';
        } elseif (in_array($ext, ['pdf', 'zim'])) {
            $type = 'document';
        } elseif (in_array($ext, ['mp3', 'wav', 'flac', 'm4a', 'ogg', 'aac'])) {
            $type = 'audio';
        } elseif (in_array($ext, ['zip', 'rar', '7z', 'tar', 'gz', 'bz2'])) {
            $type = 'archive';
        } elseif (in_array($ext, ['txt', 'md'])) {
            $type = 'text';
        } elseif (in_array($ext, ['doc', 'docx', 'rtf', 'odt'])) {
            $type = 'document';
        } elseif (in_array($ext, ['xls', 'xlsx', 'csv', 'ods'])) {
            $type = 'spreadsheet';
        } elseif (in_array($ext, ['gba', 'snes', 'smc', 'sfc', 'nes', 'gb', 'gbc', 'n64', 'z64', 'v64', 'rom', 'bin', 'iso'])) {
            $type = 'rom';
        } else {
            $type = 'file';
        }
        
        return [
            'size' => 0,
            'sizeFormatted' => 'File',
            'type' => $type,
            'extension' => $ext,
            'modified' => $modified,
            'isImage' => $isImage,
            'isVideo' => $isVideo
        ];
    }
    
    /**
     * Generate appropriate URL for file
     */
    private function generateFileUrl($relativePath, $fileInfo) {
        // Use absolute path from web root for search results
        $basePath = '/pages/files/files-browse.php';
        
        // We need to pass the full path, not just the relative path
        // Find which search path this file belongs to
        $fullPath = $fileInfo['fullPath'] ?? '';
        
        // For images and videos, use the viewer
        if ($fileInfo['isImage'] || $fileInfo['isVideo']) {
            return $basePath . '?path=' . urlencode($fullPath) . '&view=1';
        } else {
            // For other files, serve directly
            return $basePath . '?path=' . urlencode($fullPath) . '&raw=1';
        }
    }
    
    /**
     * Format file size in human readable format
     */
    private function formatFileSize($bytes) {
        if ($bytes >= 1073741824) {
            return number_format($bytes / 1073741824, 2) . ' GB';
        } elseif ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2) . ' MB';
        } elseif ($bytes >= 1024) {
            return number_format($bytes / 1024, 2) . ' KB';
        } else {
            return $bytes . ' bytes';
        }
    }
    
    /**
     * Get available books from Kiwix server dynamically
     */
    private function getKiwixBooks($kiwixUrl) {
        $books = [];
        
        try {
            // Get the catalog from Kiwix server
            $catalogUrl = $kiwixUrl . '/catalog/v2/entries';
            $context = stream_context_create([
                'http' => [
                    'timeout' => 5,
                    'ignore_errors' => true
                ]
            ]);
            
            $catalogXml = @file_get_contents($catalogUrl, false, $context);
            
            if ($catalogXml) {
                // Parse the XML catalog
                $xml = simplexml_load_string($catalogXml);
                if ($xml) {
                    // Register namespaces
                    $xml->registerXPathNamespace('atom', 'http://www.w3.org/2005/Atom');
                    $xml->registerXPathNamespace('dc', 'http://purl.org/dc/terms/');
                    
                    $entries = $xml->xpath('//atom:entry');
                    
                    foreach ($entries as $entry) {
                        $entry->registerXPathNamespace('atom', 'http://www.w3.org/2005/Atom');
                        $entry->registerXPathNamespace('dc', 'http://purl.org/dc/terms/');
                        
                        $id = (string)$entry->id;
                        $title = (string)$entry->title;
                        $summary = (string)$entry->summary;
                        
                        // Extract book ID from the content link
                        $links = $entry->xpath('atom:link[@type="text/html"]');
                        if (!empty($links)) {
                            $href = (string)$links[0]['href'];
                            // Extract book ID from href like "/book_id" 
                            if (preg_match('/\/([^\/]+)$/', $href, $matches)) {
                                $bookId = $matches[1];
                                
                                // Determine book type and category
                                $type = $this->categorizeBook($bookId, $title, $summary);
                                
                                $books[] = [
                                    'id' => $bookId,
                                    'name' => $title,
                                    'description' => $summary,
                                    'type' => $type,
                                    'language' => $this->detectLanguage($bookId, $title),
                                    'uuid' => $id
                                ];
                            }
                        }
                    }
                }
            }
        } catch (Exception $e) {
            error_log("FileSearchAPI: Error getting Kiwix books: " . $e->getMessage());
        }
        
        return $books;
    }

    /**
     * Categorize book type based on ID and title
     */
    private function categorizeBook($bookId, $title, $summary) {
        // Determine book category
        if (strpos($bookId, 'wikipedia') !== false) return 'Wikipedia';
        if (strpos($bookId, 'appropedia') !== false) return 'Sustainability';
        if (strpos($bookId, 'ifixit') !== false) return 'Repair Guides';
        if (strpos($bookId, 'urban-prepper') !== false) return 'Emergency Prep';
        if (strpos($bookId, 'stackexchange') !== false) return 'Q&A';
        if (strpos($bookId, 'devdocs') !== false) return 'Documentation';
        if (strpos($bookId, 'medicine') !== false || strpos($title, 'medicine') !== false) return 'Medical';
        if (strpos($bookId, 'openzim') !== false) return 'Reference';
        
        return 'Other';
    }

    /**
     * Detect language from book ID
     */
    private function detectLanguage($bookId, $title) {
        if (preg_match('/_([a-z]{2})_/', $bookId, $matches)) {
            return $matches[1];
        }
        return 'en'; // default to English
    }

    /**
     * Generate content suggestions based on query and book type
     */
    private function generateContentSuggestions($query, $book) {
        $suggestions = [];
        $bookType = $book['type'];
        $bookId = $book['id'];
        
        // Generate different suggestions based on book type
        switch ($bookType) {
            case 'Wikipedia':
                $suggestions = $this->generateWikipediaSuggestions($query);
                break;
            case 'Documentation':
                $suggestions = $this->generateDevDocsSuggestions($query);
                break;
            case 'Q&A':
                $suggestions = $this->generateStackExchangeSuggestions($query);
                break;
            default:
                // Generic suggestions for other content types
                $suggestions = $this->generateGenericSuggestions($query);
                break;
        }
        
        return $suggestions;
    }

    /**
     * Generate suggestions for developer documentation
     */
    private function generateDevDocsSuggestions($query) {
        $query = strtolower($query);
        $suggestions = [];
        
        // Common dev docs paths
        $paths = [
            ['path' => '', 'title' => ucfirst($query), 'description' => "Documentation for $query"],
            ['path' => 'index.html', 'title' => ucfirst($query) . ' Documentation', 'description' => "Main documentation for $query"],
            ['path' => 'api/', 'title' => ucfirst($query) . ' API', 'description' => "API reference for $query"],
            ['path' => 'guide/', 'title' => ucfirst($query) . ' Guide', 'description' => "User guide for $query"],
        ];
        
        return $paths;
    }

    /**
     * Generate suggestions for Stack Exchange content
     */
    private function generateStackExchangeSuggestions($query) {
        $query = strtolower($query);
        $suggestions = [];
        
        // Stack Exchange question patterns
        $paths = [
            ['path' => 'questions/tagged/' . urlencode($query), 'title' => "Questions about $query", 'description' => "Stack Exchange questions tagged with $query"],
            ['path' => 'search?q=' . urlencode($query), 'title' => "Search results for $query", 'description' => "Search results for $query"],
        ];
        
        return $paths;
    }

    /**
     * Generate generic suggestions for other content types
     */
    private function generateGenericSuggestions($query) {
        $query = strtolower($query);
        $suggestions = [];
        
        // Generic paths
        $paths = [
            ['path' => '', 'title' => ucfirst($query), 'description' => "Content about $query"],
            ['path' => 'index.html', 'title' => ucfirst($query), 'description' => "Main page for $query"],
            ['path' => str_replace(' ', '_', ucwords($query)), 'title' => ucwords($query), 'description' => "Article about $query"],
            ['path' => str_replace(' ', '-', strtolower($query)), 'title' => ucwords($query), 'description' => "Content about $query"],
        ];
        
        return $paths;
    }
    
    /**
     * Send JSON response
     */
    private function sendResponse($data) {
        echo json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT);
    }
}

// Handle the request
try {
    $api = new FileSearchAPI();
    $api->handleSearch();
} catch (Exception $e) {
    error_log("FileSearchAPI: Fatal error: " . $e->getMessage());
    header('HTTP/1.1 500 Internal Server Error');
    echo json_encode(['error' => 'File search service temporarily unavailable']);
}
?>
