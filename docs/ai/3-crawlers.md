<!--
title: "AI crawlers"
description: "Control which AI crawlers can access your site with robots.txt."
date: 2026-10-22
updated: 2026-10-22
-->
# AI crawlers

Cecil generates a `robots.txt` file that allows all crawlers. To block AI crawlers (or some of them), add your own rules by creating a page with the `robots` path:

_pages/robots.md_

```yaml
---
layout: robots
output: txt
---
User-agent: GPTBot
Disallow: /

User-agent: ClaudeBot
Disallow: /

User-agent: Google-Extended
Disallow: /
```

The body of the page is appended to the default rules (see [default pages](../configuration/4-pages.md#pages-default)).

:::info
`robots.txt` rules are directives: well-behaved crawlers follow them, but they don’t prevent access to your content.
:::
