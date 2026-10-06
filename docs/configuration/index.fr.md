<!--
title: "Configuration"
description: "Configurez votre site avec le fichier cecil.yml."
date: 2026-03-27
updated: 2026-10-05
weight: 5
sortby: weight
-->
# Configuration

La configuration du site web est définie dans un fichier [YAML](https://en.wikipedia.org/wiki/YAML) nommé `cecil.yml` ou `config.yml`, stocké à la racine :

```plaintext
<mywebsite>
└─ cecil.yml
```

Cecil propose de nombreuses options de configuration, mais ses [valeurs par défaut](https://github.com/Cecilapp/Cecil/blob/main/config/default.php) sont souvent suffisantes. Un nouveau site ne nécessite que ces paramètres :

```yaml
title: "My new Cecil site"
baseurl: https://mywebsite.com/
description: "Site description"
```

La documentation ci-dessous couvre toutes les options de configuration prises en charge par Cecil.
