---
title: CHANGES
---
[toc]

# 2.3.1

- added a link to view folder contents
- fixed bug on search
- close dropdowns on blur
- tweaked markup


# 2.3.0

- added headers to disable browser caching
- fixed close dropdowns when clicking outside
- added ??mark?? (Double question mark syntax) to markdown.
- Show folder/doc view switch on header

# 2.2.0

- configurable umask
- preliminary reverse-proxy support
- in cookies debug flag
- bugfix: read_only == not-auth check
- folder view now has link to document view.
- config unix_eol.  Force UNIX EOL in files.

# 2.1.0


- Added toolbars
- Removed MirrorMark
- Additional minor tweaks and doc updates
- Search functionality
- navigation improvements with file list modes: global (all files)
      or local (only current directory)
- cookie support to let the user remember how they were doing things


# 2.0.0

- Changed from PicoWiki to NanoWiki.
- moving configuration from `index.php` to `yaml` file.
- Support plugable multiple media types
  - render
  - save content
- Built-in handlers
  - Directory handler
  - Default handler with download resume support and file uploads.
- Meta data handling
- New and deprecated event hooks
- Added read-only and read|write control hooks
- All plugins re-worked
- theme support


