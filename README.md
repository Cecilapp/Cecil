# ![PHPoole logo](https://avatars2.githubusercontent.com/u/5618939?s=50 "Logo created by Cécile Ricordeau") PHPoole

> An easy and lightweight static website generator, written in PHP.

[![Build Status](https://travis-ci.org/Narno/PHPoole.svg)](https://travis-ci.org/Narno/PHPoole)
[![Coverage Status](https://coveralls.io/repos/github/Narno/PHPoole/badge.svg)](https://coveralls.io/github/Narno/PHPoole)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/Narno/PHPoole/badges/quality-score.png)](https://scrutinizer-ci.com/g/Narno/PHPoole/)
[![Codacy Badge](https://api.codacy.com/project/badge/Grade/85aa408ef2e94925831b1f7dd4c98219)](https://www.codacy.com/app/Narno/PHPoole?utm_source=github.com&amp;utm_medium=referral&amp;utm_content=Narno/PHPoole&amp;utm_campaign=Badge_Grade)
[![StyleCI](https://styleci.io/repos/12738012/shield)](https://styleci.io/repos/12738012)
[![SensioLabsInsight](https://insight.sensiolabs.com/projects/2a9ae313-1dce-405c-9632-0727ecdac269/mini.png)](https://insight.sensiolabs.com/projects/2a9ae313-1dce-405c-9632-0727ecdac269)

PHPoole takes your content (written in [Markdown](http://daringfireball.net/projects/markdown/) plain text format) and merges files with layouts ([Twig](http://twig.sensiolabs.org/) templates) to generate static HTML files.

PHPoole is a [CLI](https://en.wikipedia.org/wiki/Command-line_interface) application, powered by a [core library](https://github.com/PHPoole/PHPoole-library).

![Command line demo](https://raw.githubusercontent.com/Narno/PHPoole/master/docs/phpoole.gif)

* [Documentation](https://github.com/PHPoole/PHPoole-library/tree/master/docs) (of the core library)
* [Issue tracker](https://github.com/Narno/PHPoole/issues)

## Installation

Download the latest ```phpoole.phar``` from the [releases page](https://github.com/Narno/PHPoole/releases).
```
$ curl -SOL https://github.com/Narno/PHPoole/releases/download/X.Y.Z/phpoole.phar
$ mv phpoole.phar /usr/local/bin/phpoole
$ chmod +x /usr/local/bin/phpoole
```

## Usage

1. Create new website: ```phpoole new```
2. Build and serve it:  ```phpoole build -s```

Get help: ```phpoole help```

## Development

### Requirements

Please see the [composer.json](https://github.com/Narno/PHPoole/blob/master/composer.json) file.

### Project installation

Run the following commands:
```
$ composer create-project narno/phpoole -sdev
$ php bin/phpoole help
```

## License

PHPoole is a free software distributed under the terms of the MIT license.

PHPoole logo designed by [Cécile Ricordeau](http://www.cecillie.fr).
