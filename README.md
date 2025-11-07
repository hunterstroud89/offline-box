# Offline Box

A self-hosted offline-first web application suite designed for local network deployment. Provides a comprehensive dashboard with multiple integrated applications and services.

## Overview

Offline Box is a personal homelab interface that aggregates various web applications and utilities into a single, cohesive dashboard. Built for offline or local network environments, it provides access to entertainment, productivity tools, and system management features.

## Features

### 🏠 **Home Dashboard**
- Quick access navigation to all applications
- Service status monitoring
- Customizable background themes
- Recent files tracking

### 📁 **File Browser**
- Browse local file system
- File type icons (documents, images, videos, ROMs, archives)
- Recent files list
- File preview capabilities

### 🎮 **Retro Game Emulator**
- Powered by EmulatorJS
- Support for multiple platforms:
  - Game Boy / Game Boy Color / Game Boy Advance
  - NES / SNES / Nintendo 64 / Nintendo DS
  - Sega Genesis / Game Gear
  - PlayStation / PSP
  - Atari systems
  - And many more via RetroArch cores
- ROM cache management
- Save state support
- Download manager for game files

### 💬 **Chat Interface**
- Local chat application
- Multiple chat sessions
- Chat history storage in JSON

### 📝 **Notes**
- Simple note-taking application
- JSON-based storage
- Quick note editing

### 🤖 **Ollama Integration**
- Local AI chat interface
- Multiple conversation management
- Chat history and persistence
- Model selection support

### 🎬 **Media Services**
- Jellyfin integration
- VLC web interface with proxy
- Stream management

### 📚 **Wiki Access**
- Kiwix integration for offline Wikipedia and other content

### 🖥️ **System Tools**
- Terminal interface
- System information display
- Service status monitoring
- Admin panel for configuration

### 📖 **Comics Reader**
- Local comic book viewer

## Technical Stack

- **Frontend**: HTML, CSS, JavaScript
- **Backend**: PHP
- **Data Storage**: JSON files
- **Emulation**: EmulatorJS (RetroArch cores compiled to WebAssembly)
- **AI**: Ollama API integration

## Directory Structure

```
offline-box/
├── index.html              # Entry point (redirects to home)
├── pages/                  # Application modules
│   ├── home/              # Main dashboard
│   ├── files/             # File browser
│   ├── games/             # Retro game emulator
│   ├── chat/              # Chat application
│   ├── notes/             # Note-taking
│   ├── ollama/            # AI chat interface
│   ├── admin/             # Admin panel
│   ├── terminal/          # Web terminal
│   ├── system-info/       # System monitoring
│   ├── jellyfin/          # Media server
│   ├── vlc/               # VLC integration
│   ├── comics/            # Comic reader
│   └── simple-files/      # Simple file browser
├── data/
│   ├── json/              # Application data
│   ├── icons/             # UI icons
│   ├── backgrounds/       # Theme backgrounds
│   └── helpers/           # PHP utility scripts
└── tools/                 # Deployment and utility scripts
```

## Setup

### Requirements
- PHP 7.4 or higher
- Web server (Apache/Nginx)
- Local network environment
- Optional: Ollama for AI features
- Optional: Jellyfin for media streaming

### Installation

1. Clone the repository to your web server directory
2. Configure your web server to serve the project
3. Ensure PHP has appropriate file system permissions
4. Access via browser at `http://your-server/offline-box/`

### Configuration

The application uses PHP-based auto-configuration in `data/helpers/auto-config.php` and `config.php` to detect the environment and set appropriate paths.

## Usage

1. Navigate to the home page
2. Click on any application icon to access that module
3. Use the admin panel to configure services and customize the interface
4. Upload ROMs to the games section for emulation
5. Configure Ollama endpoint for AI chat features

## Key Features Detail

### Game Emulation
- Supports 50+ RetroArch cores
- WebAssembly-based emulation (no plugins required)
- Automatic ROM format detection
- Save states persist in browser storage
- Gamepad support

### File Management
- Recursive directory browsing
- File type detection and appropriate icons
- Recent files tracking
- Support for various file types

### AI Chat (Ollama)
- Multiple conversation threads
- Conversation history saved as JSON
- Model selection
- Streaming responses

## Development

Built as a personal homelab project for offline entertainment and productivity. Designed for single-user or small household deployment on local networks.

## Version

Current: 11.6.25 (November 6, 2025)

## License

Personal project - not licensed for redistribution.

## Author

Hunter Stroud