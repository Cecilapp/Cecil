<!--
title: CDN providers
description: Examples of CDN providers configuration.
date: 2023-10-23
updated: 2025-03-27
weight: 4
excluded: true
-->
# CDN providers

Examples of CDN providers [`configuration`](../4-Configuration.md#assets-images-cdn).

## Cloudinary

<https://cloudinary.com>

```yaml
assets:
  images:
    cdn:
      enabled: true
      account: 'xxxx'
      url: 'https://res.cloudinary.com/%account%/image/fetch/c_limit,w_%width%,q_%quality%,f_%format%,d_default/%image_url%'
```

## Cloudimage

<https://www.cloudimage.io>

```yaml
assets:
  images:
    cdn:
      enabled: true
      account: 'xxxx'
      url: 'https://%account%.cloudimg.io/%image_url%?w=%width%&q=%quality%&force_format=%format%'
```

## TwicPics

<https://www.twicpics.com>

```yaml
assets:
  images:
    cdn:
      enabled: true
      account: 'xxxx'
      canonical: false
      remote: false
      url: 'https://%account%.twic.pics/%image_url%?twic=v1/resize=%width%/quality=%quality%/output=%format%'
```

`Source URL`: Your website `baseurl`.

## imgix

<https://imgix.com>

```yaml
assets:
  images:
    cdn:
      enabled: true
      account: 'xxxx'
      canonical: false
      remote: false
      url: 'https://%account%.imgix.net/%image_url%?w=%width%&q=%quality%&fm=%format%'
```

`Base URL`: Your website `baseurl`.

## Netlify Image CDN

<https://docs.netlify.com/image-cdn/overview/>

```yaml
assets:
  images:
    cdn:
      enabled: true
      canonical: false
      url: '/.netlify/images?url=%image_url%&w=%width%&fm=%format%'
```

### Run locally

#### Setup Netlify CLI

```bash
npm install netlify-cli -g
netlify link
```

`netlify.toml`:

```yaml
[dev]
  targetPort = 8000
```

#### Run local server

```bash
php cecil.phar serve & netlify dev
```

Open <http://localhost:8888>
