<!--
title: "Configuration"
description: "Configure your website with the cecil.yml file."
date: 2021-05-07
updated: 2026-10-05
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

The following documentation covers all supported configuration options in Cecil.
