<!--
title: "Configuration"
description: "Configure your website with the cecil.yml file."
date: 2021-05-07
updated: 2026-10-17
weight: 5
sortby: weight
-->
# Configuration

The website configuration is defined in a [YAML](https://en.wikipedia.org/wiki/YAML) file named `cecil.yml` or `config.yml` stored at the root:

```plaintext
<mywebsite>
└─ cecil.yml
```

Cecil offers many configuration options, but its [defaults](https://github.com/Cecilapp/Cecil/blob/main/config/default.php) are often sufficient. A new site requires only these settings:

```yaml
title: "My new Cecil site"
baseurl: https://mywebsite.com/
description: "Site description"
```

The following pages describe the configuration options supported by Cecil.

Some options specific to a command are documented with that command: `editor` ([`new:page`](../commands/2-new-page.md#open-with-your-editor)) and `doctor.*` ([`doctor`](../commands/5-doctor.md)).

## Lists

When an option is a list of values (e.g. [`pages.ext`](4-pages.md), [`output.pagetypeformats`](8-output.md#output-pagetypeformats) or [`assets.images.responsive.widths`](6-assets.md)), the list you define **replaces** the default one:

```yaml
output:
  pagetypeformats:
    homepage: [html] # only HTML, regardless of the defaults
```

If a [theme](../templates/7-extend.md#theme) defines the same list, the values of your list are **added** to the theme ones, as they can be required by the theme.

To **adjust** the default (or theme) list instead, use a map: `true` adds a value, `false` removes it.

```yaml
output:
  pagetypeformats:
    homepage:
      rss: true   # adds RSS
      atom: false # removes Atom
assets:
  images:
    responsive:
      widths:
        2560: true # adds a width
        480: false # removes a width
```
