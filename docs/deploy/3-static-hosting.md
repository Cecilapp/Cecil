<!--
title: "Static hosting"
description: "Deploy to a static hosting service (Surge, deed.page)."
date: 2020-12-19
updated: 2026-10-29
-->
# Static hosting

The built site can be uploaded as is to any static hosting service.

## Surge

> Shipping web projects should be fast, easy, and low risk. Surge is static web publishing for Front-End Developers, right from the CLI.

➡️ <https://surge.sh>

Terminal:

```bash
npm install -g surge
surge _site/
```

[Official documentation](https://surge.sh/help/)

## deed.page

> Static hosting with no account: upload the built site in one request and get a permanent `https://<slug>.deed.page/` URL.

➡️ <https://deed.page>

Terminal:

```bash
cecil build --baseurl=https://my-cecil-site.deed.page/
tar -C _site -czf site.tar.gz .
curl -T site.tar.gz "https://deed.page/v1/sites?slug=my-cecil-site"
```

Replace `my-cecil-site` with a unique slug of your choice.  
The JSON response includes a token that is shown only once: keep it private, and send it as an `Authorization: Bearer` header to update the same slug later. A single request is limited to 4 MB (larger sites can use a manifest upload).

[Official documentation](https://deed.page/docs)
