# ![PHPoole logo](https://avatars2.githubusercontent.com/u/5618939?s=50 "Logo created by Cécile Ricordeau") PHPoole

> An easy and lightweight static website generator, written in PHP.

[![Build Status](https://travis-ci.org/PHPoole/PHPoole.svg)](https://travis-ci.org/PHPoole/PHPoole)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/PHPoole/PHPoole/badges/quality-score.png)](https://scrutinizer-ci.com/g/PHPoole/PHPoole/)
[![Codacy Badge](https://api.codacy.com/project/badge/Grade/85aa408ef2e94925831b1f7dd4c98219)](https://www.codacy.com/app/Narno/PHPoole?utm_source=github.com&amp;utm_medium=referral&amp;utm_content=PHPoole/PHPoole&amp;utm_campaign=Badge_Grade)
[![StyleCI](https://styleci.io/repos/12738012/shield)](https://styleci.io/repos/12738012)
[![SensioLabsInsight](https://insight.sensiolabs.com/projects/2a9ae313-1dce-405c-9632-0727ecdac269/mini.png)](https://insight.sensiolabs.com/projects/2a9ae313-1dce-405c-9632-0727ecdac269)

PHPoole takes your content (written in [Markdown](http://daringfireball.net/projects/markdown/) plain text format) and merges files with layouts ([Twig](http://twig.sensiolabs.org/) templates) to generate static HTML files.

PHPoole is a [CLI](https://en.wikipedia.org/wiki/Command-line_interface) application, powered by the [PHPoole core library](https://github.com/PHPoole/PHPoole-library), highly inspired by [Jekyll](http://jekyllrb.com/) and [Hugo](http://gohugo.io/).

![Command line demo](https://raw.githubusercontent.com/PHPoole/PHPoole/master/docs/phpoole.gif)

* [Documentation](https://phpoole.org/documentation)
* [Issue tracker](https://github.com/PHPoole/PHPoole/issues)

## Features

* No database, files only
* Flexible template engine & themes support
* Dynamic menu creation
* Generators (taxonomies, paginaton, etc.)

## Installation

```
$ curl -SOL https://phpoole.org/phpoole.phar
$ mv phpoole.phar /usr/local/bin/phpoole
$ chmod +x /usr/local/bin/phpoole
```

## Usage

* Create new website: ```phpoole new```
* Build and serve it:  ```phpoole build -s```
* Get help: ```phpoole help```

Try the [demo](https://github.com/PHPoole/PHPoole-demo)!

[![Deploy to Netlify](https://www.netlify.com/img/deploy/button.svg)](https://app.netlify.com/start/deploy?repository=https://github.com/PHPoole/Cecil)

## Development

### Requirements

Please see the [composer.json](https://github.com/PHPoole/PHPoole/blob/master/composer.json) file.

### Project installation

Run the following commands:
```
$ composer create-project narno/phpoole -sdev
$ cd phpoole
$ php bin/phpoole -v
```

## License

PHPoole is a free software distributed under the terms of the MIT license.

© 2017 [Arnaud Ligny](https://arnaudligny.fr)  
PHPoole logo © [Cécile Ricordeau](http://www.cecillie.fr)
