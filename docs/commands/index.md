<!--
title: "Commands"
description: "Reference of Cecil CLI commands and options."
date: 2020-12-19
updated: 2026-10-02
weight: 6
sortby: weight
-->
# Commands

List of available commands.

```plaintext
Available commands:
  about                      Shows a short description about Cecil
  build                      Builds the website
  clear                      Removes all generated files
  doctor                     Diagnoses the site configuration
  edit                       [open] Open pages directory with the editor
  help                       Display help for a command
  serve                      Starts the built-in server
 cache
  cache:clear                Removes all cache files
  cache:clear:assets         Removes assets cache
  cache:clear:templates      Removes templates cache
  cache:clear:translations   Removes translations cache
 clear
  clear:output               Removes output directory
  clear:temporary            [clear:tmp] Removes temporary directory
 doctor
  doctor:frontmatter         [doctor:fm] Validates pages front matter syntax
  doctor:cache               Shows cache status
  doctor:seo                 Audits rendered HTML pages for common SEO issues
 new
  new:page                   Creates a new page
  new:site                   Creates a new website
 serve
  serve:background           Starts the built-in server in the background
  serve:stop                 [stop] Stops the background server
  serve:log                  Shows combined server and error logs
 show
  show:config                Shows the configuration
  show:content               Shows content as tree
 util
  util:templates:extract     Extracts built-in templates
  util:translations:extract  Extracts translations from templates
```
