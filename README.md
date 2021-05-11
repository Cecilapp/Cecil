# Cecil

![Cecil logo](https://cecil.app/images/cecil-logo-netlify-cms.png "Logo created by Cécile Ricordeau")

> Your content driven static site generator.

[![Latest Stable Version](https://poser.pugx.org/cecil/cecil/v/stable)](https://github.com/Cecilapp/Cecil/releases/latest)
[![Test](https://github.com/Cecilapp/Cecil/workflows/Test/badge.svg)](https://github.com/Cecilapp/Cecil/actions?query=workflow%3ATest)
[![Release cecil.phar](https://github.com/Cecilapp/Cecil/workflows/Release%20cecil.phar/badge.svg)](https://github.com/Cecilapp/Cecil/actions?query=workflow%3A%22Release+cecil.phar%22)
[![License](https://poser.pugx.org/cecil/cecil/license)](https://github.com/Cecilapp/Cecil/blob/master/LICENSE)  
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/Cecilapp/Cecil/badges/quality-score.png)](https://scrutinizer-ci.com/g/Cecilapp/Cecil/)
[![Codacy Badge](https://app.codacy.com/project/badge/Grade/07232d3c7ff34f3da5abdac8f3ad2cee)](https://www.codacy.com/gh/Cecilapp/Cecil/dashboard)
[![Coverage Status](https://coveralls.io/repos/github/Cecilapp/Cecil/badge.svg?branch=master)](https://coveralls.io/github/Cecilapp/Cecil?branch=master)
[![StyleCI](https://github.styleci.io/repos/7548986/shield?style=plastic)](https://styleci.io/repos/12738012)
[![SymfonyInsight](https://insight.symfony.com/projects/2a9ae313-1dce-405c-9632-0727ecdac269/mini.png)](https://insight.symfony.com/projects/2a9ae313-1dce-405c-9632-0727ecdac269)

Cecil is a CLI application that merges plain text files (written in [Markdown](http://daringfireball.net/projects/markdown/)), images and [Twig](https://twig.symfony.com) templates to generate a [static website](https://en.wikipedia.org/wiki/Static_web_page).

📄 [Documentation](https://cecil.app/documentation) | 💻 [Demo](https://demo.cecil.app) | 🐛 [Issue tracker](https://github.com/Cecilapp/Cecil/issues) | 💬 [Discussion](https://github.com/Cecilapp/Cecil/discussions)

![Cecil CLI demo](docs/cecil-demo.gif "Cecil CLI demo")

## Quick Start

Read the [Quick Start](https://cecil.app/documentation/quick-start/) documentation page.

[![Deploy to Netlify](https://www.netlify.com/img/deploy/button.svg)](https://cecil.app/hosting/netlify/deploy/) [![Deploy with Vercel](https://vercel.com/button)](https://cecil.app/hosting/vercel/deploy/) [![Import this project into Forestry](https://assets.forestry.io/import-to-forestryK.svg)](https://cecil.app/cms/forestry/import/)

## Features

- No server, no database, no dependency: performance and security
- Content is stored in flat files, written in [Markdown](https://daringfireball.net/projects/markdown/) with [YAML front matter](https://cecil.app/documentation/content/#front-matter)
- Powered by [Twig](https://twig.symfony.com/doc/templates.html), a flexible template engine, with [theme](https://github.com/Cecilapp/theme-hyde) support
- Pagination, taxonomies, RSS, redirections, etc. are generated automatically
- [Download](https://cecil.app/download/) just one file and run it
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

[![Aperture Lab](https://avatars.githubusercontent.com/u/10225022?s=100 "Aperture Lab")](https://aperturelab.fr) [![studio cecillie](https://raw.githubusercontent.com/cecillie/eshop/main/static/images/cecillie_signature.png "studio cecillie")](https://studio.cecillie.fr)

## License

Cecil is a free software distributed under the terms of the MIT license.

Cecil © [Arnaud Ligny](https://arnaudligny.fr)  
Logo © [Cécile Ricordeau](https://www.cecillie.fr)
