---
title: Backlog
tags: development, php
---
[toc]


# Navigation

- nav
  - tag-cloud [all files|current context]
  - sort - alpha,latest file
- tags: GET to add or remove tags from the selection cookie


# Markdown text diagrams

- blockdiag
  - http://blockdiag.com/en/
- [x] aafigure (or equivalent)
  - https://git.alpinelinux.org/aports/tree/testing/svgbob/APKBUILD
  - https://pkgs.alpinelinux.org/package/edge/testing/x86_64/svgbob
  - https://dl-cdn.alpinelinux.org/alpine/edge/testing/x86_64/svgbob-0.6.6-r0.apk
  - https://github.com/ivanceras/svgbob
- [x] graphviz : as an input filter
  - apk add graphviz font-bitstream-type1 ghostscript-fonts
  - [ ] fix png generation fonts
- update container config


# new media types

- source code
  - only view source in codemirror
  - read meta data from comments (start-of-header, line-comment, end-of-header)
  - 404 handler: create new file

# others

- user authentication
  - https://www.devdungeon.com/content/http-basic-authentication-php
- http daemon authentication
  - https://httpd.apache.org/docs/2.4/howto/auth.html
- add front-matter-yaml supbaport
  - md : when saving, check yaml
  - getRemoteUser
      - http user?
      - remote IP
  - if file does not exist
  - created: <date> <remote-user>
  - updated-by: <remote-user>
  - if (log in meta/yaml) {
    make log empty
    change-log: <date> <remote-user> <log-msg>
  - [x] auto-meta-data: date
- tagging
  - [ ] auto-tagging: based on words and tagcloud
  - tag from git
  - auto-tags: automatically generated
  - tags: manual tags
  - exclude-tags: removed.
- Report for checking for broken links (links to pages that don't exist yet), Orphan pages, etc.
- Code snippets to load YouTube videos or Google Maps, etc.
- Sitemap generator
- reverse proxy support
- implement a dark theme
- markdown media handler
  - if yaml contains enable-php true
  - run PHP code

# todo

- Fix move/rename logic?
- Create a new file doesn't path very well.
- local links instead of absolute links
- dev env
  - container deploy: set permissions right
  - move to nd2, and export files and mount on nd3
