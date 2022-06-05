[![Cecil's logo, created by Cécile Ricordeau](https://cecil.app/images/logo-cecil.png)](https://cecil.app)

Cecil, your content driven static site generator.

[![Latest Stable Version](https://poser.pugx.org/cecil/cecil/v/stable)](https://github.com/Cecilapp/Cecil/releases/latest)
[![Latest Preview Version](https://poser.pugx.org/cecil/cecil/v/unstable)](https://github.com/Cecilapp/Cecil/releases)
[![License](https://poser.pugx.org/cecil/cecil/license)](https://github.com/Cecilapp/Cecil/blob/master/LICENSE)  
[![Tests suite](https://github.com/Cecilapp/Cecil/actions/workflows/test.yml/badge.svg)](https://github.com/Cecilapp/Cecil/actions/workflows/test.yml)
[![Release cecil.phar](https://github.com/Cecilapp/Cecil/actions/workflows/release.yml/badge.svg)](https://github.com/Cecilapp/Cecil/actions/workflows/release.yml)
[![Deploy documentation](https://github.com/Cecilapp/Cecil/actions/workflows/docs.yml/badge.svg)](https://github.com/Cecilapp/Cecil/actions/workflows/docs.yml)  
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/Cecilapp/Cecil/badges/quality-score.png)](https://scrutinizer-ci.com/g/Cecilapp/Cecil/)
[![Codacy Badge](https://app.codacy.com/project/badge/Grade/07232d3c7ff34f3da5abdac8f3ad2cee)](https://www.codacy.com/gh/Cecilapp/Cecil/dashboard)
[![Coverage Status](https://coveralls.io/repos/github/Cecilapp/Cecil/badge.svg?branch=master)](https://coveralls.io/github/Cecilapp/Cecil?branch=master)
[![StyleCI](https://github.styleci.io/repos/7548986/shield?style=plastic)](https://styleci.io/repos/12738012)
[![SymfonyInsight](https://insight.symfony.com/projects/ada27715-6342-43f8-a1e7-4d5a8fe78e62/mini.svg)](https://insight.symfony.com/projects/ada27715-6342-43f8-a1e7-4d5a8fe78e62)

Cecil is a CLI application that merges plain text files (written in [Markdown](http://daringfireball.net/projects/markdown/)), images and [Twig](https://twig.symfony.com) templates to generate a [static website](https://en.wikipedia.org/wiki/Static_web_page).

📄 [Documentation](https://cecil.app/documentation) | 💻 [Demo](https://demo.cecil.app) | 🐛 [Issue tracker](https://github.com/Cecilapp/Cecil/issues) | 💬 [Discussion](https://github.com/Cecilapp/Cecil/discussions)

![Cecil CLI demo](docs/cecil-cli-demo.gif "Cecil CLI demo")

## Quick Start

Read the [Quick Start](https://cecil.app/documentation/quick-start/) documentation page.

[![Deploy to Netlify](https://www.netlify.com/img/deploy/button.svg "Deploy to Netlify")](https://cecil.app/hosting/netlify/deploy/) [![Deploy with Vercel](https://vercel.com/button "Deploy with Vercel")](https://cecil.app/hosting/vercel/deploy/) [![Import to Forestry](https://assets.forestry.io/import-to-forestryK.svg "Import to Forestry")](https://cecil.app/cms/forestry/import/)

## Features

- No database, no server, no dependency: performance and security
- Your content is stored in [Markdown](https://daringfireball.net/projects/markdown/) flat files with a [YAML front matter](https://cecil.app/documentation/content/#front-matter)
- Powered by [Twig](https://twig.symfony.com/doc/templates.html), a flexible template engine, with [themes](https://cecil.app/themes) support
- Pagination, sitemap, redirections, robots.txt, taxonomies, RSS are generated automatically
- [Download](https://cecil.app/download/) one file and run it
- Easy to deploy

## Installation

[Download `cecil.phar`](https://github.com/Cecilapp/Cecil/releases/latest/download/cecil.phar) from your browser or from your terminal:

```bash
curl -LO https://cecil.app/cecil.phar
```

Then install the binary globally:

```bash
mv cecil.phar /usr/local/bin/cecil
chmod +x /usr/local/bin/cecil
```

> [PHP](https://www.php.net) 7.1+ is required.

## Usage

- Get help: `cecil help`
- Create new website: `cecil new:site`
- Build and serve it: `cecil serve`

## Contributing

See [Contributing](CONTRIBUTING.md).

## Sponsors

<!--[![Aperture Lab](https://avatars.githubusercontent.com/u/10225022?s=100)](https://aperturelab.fr)&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;-->
[![studio cecillie](https://raw.githubusercontent.com/Cecilapp/website/master/static/images/logos/cecillie.png)](https://studio.cecillie.fr#gh-light-mode-only)[![studio cecillie](https://raw.githubusercontent.com/Cecilapp/website/master/static/images/logos/cecillie-dark.png)](https://studio.cecillie.fr#gh-dark-mode-only)&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<!--[![Netlify](https://raw.githubusercontent.com/Cecilapp/website/master/static/images/logos/netlify.png)](https://www.netlify.com#gh-light-mode-only)[![Netlify](https://raw.githubusercontent.com/Cecilapp/website/master/static/images/logos/netlify-dark.png)](https://www.netlify.com#gh-dark-mode-only)&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;-->[![Vercel](https://raw.githubusercontent.com/Cecilapp/website/master/static/images/logos/vercel.png)](https://vercel.com/?utm_source=cecil&utm_campaign=oss#gh-light-mode-only)[![ Vercel](https://raw.githubusercontent.com/Cecilapp/website/master/static/images/logos/vercel-dark.png)](https://vercel.com/?utm_source=cecil&utm_campaign=oss#gh-dark-mode-only)

## License

Cecil is a free software distributed under the terms of the MIT license.

Cecil © [Arnaud Ligny](https://arnaudligny.fr)  
Logo © [Cécile Ricordeau](https://www.cecillie.fr)
