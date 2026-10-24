---
title: Configuration
description: How to configure the website with the cecil.yml file.
---
# Configuration

The website is configured in the `cecil.yml` file, at the root of the project.

## Main options

```yaml
title: Site title
baseline: Site baseline
baseurl: https://domain.tld/
description: Site description.
language: en
```

- `title`, `baseline` and `description` are used in templates and meta tags
- `baseurl` is the URL of the website once deployed
- `language` is the main language code of the content

## Menus

Pages are added to a menu with the `menu` variable of their front matter:

```yaml
---
title: About
menu:
  main:
    weight: 30
---
```

Other entries can be added in the configuration:

```yaml
menus:
  main:
    - id: github
      name: GitHub
      url: https://github.com/
      weight: 99
```

## Learn more

All the options are described in the [configuration documentation](https://cecil.app/documentation/configuration/).
