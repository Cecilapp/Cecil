# ![Cecil logo](https://avatars2.githubusercontent.com/u/5618939?s=50 "Logo created by Cécile Ricordeau") Cecil

> Your content driven static site generator.

[![Build Status](https://travis-ci.org/PHPoole/Cecil.svg)](https://travis-ci.org/PHPoole/Cecil)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/PHPoole/Cecil/badges/quality-score.png)](https://scrutinizer-ci.com/g/PHPoole/Cecil/)
[![Codacy Badge](https://api.codacy.com/project/badge/Grade/85aa408ef2e94925831b1f7dd4c98219)](https://www.codacy.com/app/Narno/Cecil)
[![StyleCI](https://styleci.io/repos/12738012/shield)](https://styleci.io/repos/12738012)
[![SymfonyInsight](https://insight.symfony.com/projects/2a9ae313-1dce-405c-9632-0727ecdac269/mini.png)](https://insight.symfony.com/projects/2a9ae313-1dce-405c-9632-0727ecdac269)

Cecil is a CLI application that merges plain text files (written in [Markdown](http://daringfireball.net/projects/markdown/)), images and [Twig](http://twig.sensiolabs.org/) templates to generate a [static website](https://en.wikipedia.org/wiki/Static_web_page).

![Cecil CLI demo](https://raw.githubusercontent.com/PHPoole/PHPoole/master/docs/cecil-cli.gif "Cecil CLI demo")

- [Documentation](https://cecil.app/documentation)
- [Issue tracker](https://github.com/PHPoole/Cecil/issues)

## Features

- No database, files only
- Flexible template engine & themes support
- Dynamic menu creation
- Generators (taxonomies, paginaton, etc.)

## Installation

```bash
curl -SOL https://cecil.app/cecil.phar
mv cecil.phar /usr/local/bin/cecil
chmod +x /usr/local/bin/cecil
```

> PHP 7.1+ is required.

## Usage

- Create new website: `cecil new`
- Build and serve it: `cecil serve`
- Get help: `cecil help`

## Quick Start

Read the [Quick Start](https://phpoole.org/documentation/quick-start/) documentation page.

[![Deploy to Netlify](https://www.netlify.com/img/deploy/button.svg)](https://app.netlify.com/start/deploy?repository=https://github.com/PHPoole/Cecil-starter-blog-Hyde) a ready to go [blog](https://github.com/PHPoole/Cecil-starter-blog-Hyde).

## Development

### Requirements

Please see the [composer.json](https://github.com/PHPoole/Cecil/blob/master/composer.json) file.

### Project installation

Run the following commands:

```bash
composer create-project phpoole/cecil -sdev
cd cecil
php bin/cecil
```

## License

Cecil / Cecil is a free software distributed under the terms of the MIT license.

PHPoole / Cecil © [Arnaud Ligny](https://arnaudligny.fr)  
PHPoole / Cecil logo © [Cécile Ricordeau](http://www.cecillie.fr)
