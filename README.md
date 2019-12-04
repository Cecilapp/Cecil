# ![Cecil logo](https://avatars2.githubusercontent.com/u/45047331?s=50 "Logo created by Cécile Ricordeau") Cecil

> Your content driven static site generator.

[![Latest Stable Version](https://poser.pugx.org/cecil/cecil/v/stable)](https://github.com/Cecilapp/Cecil/releases/latest)
[![Build Status](https://travis-ci.org/Cecilapp/Cecil.svg?branch=master)](https://travis-ci.org/Cecilapp/Cecil)
[![License](https://poser.pugx.org/cecil/cecil/license)](https://github.com/Cecilapp/Cecil/blob/master/LICENSE)  
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/Cecilapp/Cecil/badges/quality-score.png)](https://scrutinizer-ci.com/g/Cecilapp/Cecil/)
[![Codacy Badge](https://api.codacy.com/project/badge/Grade/85aa408ef2e94925831b1f7dd4c98219)](https://www.codacy.com/app/Narno/Cecil)
[![Coverage Status](https://coveralls.io/repos/github/Cecilapp/Cecil/badge.svg?branch=master)](https://coveralls.io/github/Cecilapp/Cecil?branch=master)
[![StyleCI](https://styleci.io/repos/12738012/shield)](https://styleci.io/repos/12738012)
[![SymfonyInsight](https://insight.symfony.com/projects/2a9ae313-1dce-405c-9632-0727ecdac269/mini.png)](https://insight.symfony.com/projects/2a9ae313-1dce-405c-9632-0727ecdac269)

Cecil is a CLI application that merges plain text files (written in [Markdown](http://daringfireball.net/projects/markdown/)), images and [Twig](https://twig.symfony.com) templates to generate a [static website](https://en.wikipedia.org/wiki/Static_web_page).

![Cecil CLI demo](https://raw.githubusercontent.com/Cecilapp/Cecil/master/docs/cecil-demo.png "Cecil CLI demo")

- [Documentation](https://cecil.app/documentation)
- [Issue tracker](https://github.com/Cecilapp/Cecil/issues)
- [Demo](https://demo.cecil.app)

## Features

- No server, no database, no dependency: performance and security
- Content is stored in flat files, written in Markdown with YAML front matter
- Powered by Twig, a flexible template engine, with theme support
- Paginaton, taxonomies, redirections (and more !) are generated automatically

## Installation

```bash
curl -LO https://cecil.app/cecil.phar
mv cecil.phar /usr/local/bin/cecil
chmod +x /usr/local/bin/cecil
```

> [PHP](https://www.php.net) 7.1+ is required.

## Usage

- Get help: `cecil help`
- Create new website: `cecil newsite`
- Build and serve it: `cecil serve`

## Quick Start

Read the [Quick Start](https://cecil.app/documentation/quick-start/) documentation page.

[![Import this project into Forestry](https://assets.forestry.io/import-to-forestryK.svg)](https://app.forestry.io/quick-start?repo=cecilapp/starter-blog) [![Deploy to Netlify](https://www.netlify.com/img/deploy/button.svg)](https://app.netlify.com/start/deploy?repository=https://github.com/Cecilapp/starter-blog) [![Deploy with ZEIT Now](https://zeit.co/button)](https://zeit.co/new/project?template=https://github.com/Cecilapp/starter-blog)

## License

Cecil is a free software distributed under the terms of the MIT license.

Cecil © [Arnaud Ligny](https://arnaudligny.fr)  
Logo © [Cécile Ricordeau](https://www.cecillie.fr)
