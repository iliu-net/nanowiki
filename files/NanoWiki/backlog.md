---
title: Backlog
tags: development, php
---
[toc]

Rename as NanoWiki was already used:

- NacoWiki
- ***
- Alecy
- Lalex
- Niki or NikiWiki
- PinoWiki
- Piano
- Naniki


***

- [x] close pop-up when other pop-up opens
- re-structure code

***

- re-vamp UI
  - link to copy URL (local/global) to clipboard (for inserting into
    articles)
  - TOOLBAR
    - home, folder, search, filesmenu, toolmenu | save,view/source
- Fix move/rename logic?
- Create a new file doesn't path very well.
- local links instead of absolute links
- WikiLinks if no `/` but a `!` should search the name all
  over the place.
- Search : checkbox for global search and local search
- First search should display a virtual search view.
- sort - alpha,latest file
- Switch to source should hit the server.
  - preview without saving article

# re-vamp FILES pop-up UI

- treat it as a small web applet.
- we send the full document list
- java script will:
  - render the list
    - provide a link to copy URL to clipboard for inserting into article
    - the local/global view switch
    - display as a tree view
    - do the search thing

# Tag Navigation

- nav
  - tag-cloud [all files|current context]
- tags: GET to add or remove tags from the selection cookie
- tagging
  - [ ] auto-tagging: based on words and tagcloud
  - tag from git
  - auto-tags: automatically generated
  - tags: manual tags
  - exclude-tags: removed.


# Markdown text diagrams

- blockdiag
  - http://blockdiag.com/en/

# Other diag integrations

- https://github.com/cidrblock/drawthe.net
- https://github.com/jgraph/drawio

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
- add front-matter-yaml support
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
- Report for checking for broken links (links to pages that don't exist yet), Orphan pages, etc.
- Code snippets to load YouTube videos or Google Maps, etc.
- Sitemap generator
- implement a dark theme
- markdown media handler
  - if yaml contains enable-php true
  - run PHP code

