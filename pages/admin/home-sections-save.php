<?php
$file = __DIR__ . '/../../data/json/home_sections.json';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  echo json_encode(['error' => 'Method not allowed']);
  exit;
}

$body = file_get_contents('php://input');
$data = json_decode($body, true);
if (!is_array($data)) {
  http_response_code(400);
  echo json_encode(['error' => 'Invalid JSON']);
  exit;
}

if (file_put_contents($file, json_encode($data, JSON_PRETTY_PRINT))) {
  echo json_encode(['ok' => true]);
} else {
  http_response_code(500);
  echo json_encode(['error' => 'Failed to write file']);
}
