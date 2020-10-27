# Cecil

![Cecil logo](https://cecil.app/images/cecil-logo-netlify-cms.png "Logo created by Cécile Ricordeau")

> Your content driven static site generator.

[![Latest Stable Version](https://poser.pugx.org/cecil/cecil/v/stable)](https://github.com/Cecilapp/Cecil/releases/latest)
[![Build Status](https://travis-ci.org/Cecilapp/Cecil.svg?branch=master)](https://travis-ci.org/Cecilapp/Cecil)
[![License](https://poser.pugx.org/cecil/cecil/license)](https://github.com/Cecilapp/Cecil/blob/master/LICENSE)  
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/Cecilapp/Cecil/badges/quality-score.png)](https://scrutinizer-ci.com/g/Cecilapp/Cecil/)
[![Codacy Badge](https://api.codacy.com/project/badge/Grade/85aa408ef2e94925831b1f7dd4c98219)](https://www.codacy.com/app/Narno/Cecil)
[![Coverage Status](https://coveralls.io/repos/github/Cecilapp/Cecil/badge.svg?branch=master)](https://coveralls.io/github/Cecilapp/Cecil?branch=master)
[![StyleCI](https://github.styleci.io/repos/7548986/shield?style=plastic)](https://styleci.io/repos/12738012)
[![SymfonyInsight](https://insight.symfony.com/projects/2a9ae313-1dce-405c-9632-0727ecdac269/mini.png)](https://insight.symfony.com/projects/2a9ae313-1dce-405c-9632-0727ecdac269)

Cecil is a CLI application that merges plain text files (written in [Markdown](http://daringfireball.net/projects/markdown/)), images and [Twig](https://twig.symfony.com) templates to generate a [static website](https://en.wikipedia.org/wiki/Static_web_page).

💻 [Demo](https://demo.cecil.app) | 📄 [Documentation](https://cecil.app/documentation) | 🐛 [Issue tracker](https://github.com/Cecilapp/Cecil/issues)

![Cecil CLI demo](docs/cecil-demo.gif "Cecil CLI demo")

## Quick Start

Read the [Quick Start](https://cecil.app/documentation/quick-start/) documentation page.

[![Import this project into Forestry](https://assets.forestry.io/import-to-forestryK.svg)](https://cecil.app/cms/forestry/import/) [![Deploy to Netlify](https://www.netlify.com/img/deploy/button.svg)](https://cecil.app/hosting/netlify/deploy/) [![Deploy with Vercel](https://vercel.com/button)](https://cecil.app/hosting/vercel/deploy/)

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

## License

Cecil is a free software distributed under the terms of the MIT license.

Cecil © [Arnaud Ligny](https://arnaudligny.fr)  
Logo © [Cécile Ricordeau](https://www.cecillie.fr)
