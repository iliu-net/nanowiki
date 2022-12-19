---
title: Backlog
tags: development, php
---
[toc]

***

- Fix move/rename logic?
- Create a new file doesn't path very well.
- local links instead of absolute links
- WikiLinks if no `/` but a `!` should search the name all
  over the place.
- Search : checkbox for global search and local search
- Search: multiple matches in a single file causes the file to listed multiple times
- First search should display a virtual search view.
- sort - alpha,latest file

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

