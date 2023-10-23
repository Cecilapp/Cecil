---
title: CDN providers
description: Examples of CDN providers configuration.
date: 2023-10-23
weight: 4
exclude: true
---
# CDN providers

Examples of CDN providers configuration.

## [Cloudinary](https://cloudinary.com)

```yaml
assets:
  images:
    cdn:
      enabled: true
      account: 'xxxx'
      url: 'https://res.cloudinary.com/%account%/image/fetch/c_limit,w_%width%,q_%quality%,f_%format%,d_default/%image_url%'
```

## [Cloudimage](https://www.cloudimage.io)

```yaml
assets:
  images:
    cdn:
      enabled: true
      account: 'xxxx'
      url: 'https://%account%.cloudimg.io/%image_url%?w=%width%&q=%quality%&force_format=%format%'
```

## [TwicPics](https://www.twicpics.com)

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

## [imgix](https://imgix.com)

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
