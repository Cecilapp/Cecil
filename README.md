PHPoole is a light and easy static website generator written in PHP.
It takes your content (written in [Markdown](http://daringfireball.net/projects/markdown/) plain text format), merges it with layouts ([Twig](http://twig.sensiolabs.org/) templates) and generates static HTML files.

Branches:
* [1.5.0-dev](https://github.com/Narno/PHPoole/tree/1.5.0-dev): Decoupled code, uses [ZF\Console](https://github.com/zfcampus/zf-console)
* 2.0.0-dev: Based on the [new library](https://github.com/Narno/PHPoole-library), _WIP_

**Q/A:**

* Why the name _PHPoole_? It is [PHP](http://www.php.net) + [Poole](http://en.wikipedia.org/wiki/Strange_Case_of_Dr_Jekyll_and_Mr_Hyde#Mr._Poole) (in reference to [Jekyll Ruby](http://jekyllrb.com))
* Is PHPoole is stable? It is still in development, be careful!
* Is there a demo? Yes there is, see [PHPoole/demo](https://github.com/PHPoole/demo)
* How to get support? Through [GitHub issues](https://github.com/Narno/PHPoole/issues) system
* Can I contribute? Yes you could submit a [Pull Request](https://help.github.com/articles/using-pull-requests) on [GitHub](https://github.com/Narno/PHPoole)

Requirements
------------

### Use

* [PHP](https://github.com/php) 5.4+
* [Git](http://git-scm.com) (to deploy on GitHub Pages)

### Install plugins

* [Composer](http://getcomposer.org) (to install and update)

### Development

* [Composer](http://getcomposer.org) (to install / update dependencies)
 * [ZF2 components](https://github.com/zendframework)
 * [PHP Markdown](https://github.com/michelf/php-markdown)
 * [Twig](https://github.com/fabpot/Twig)


Quick Start
-----------

### 1. Get PHPoole
    $ curl -SO http://phpoole.narno.org/downloads/phpoole.phar

### 2. Initialize a new website
    $ php phpoole.phar --init

### 3. Generate the static website
    $ php phpoole.phar --generate

### 4. Serve the local website
    $ php phpoole.phar --serve

### 5. Deploy the website on GitHub Pages
    $ php phpoole.phar --deploy

----

Usage
-----

### Get PHPoole
    
    $ curl -SO http://phpoole.narno.org/downloads/phpoole.phar


### Initialize

Once PHPoole is downloaded, run the following command to build all files you need (in the curent or target folder).

    $ php phpoole.phar [folder] --init

Alias: ```$ php phpoole.phar [folder] -i```

Note: You can force initialization of an already initialized folder.

    $ php phpoole.phar [folder] --init=force

After ```--init```, here's how the folder looks like:

    [folder]
    +-- _phpoole
        +-- assets
        |   +-- css
        |   +-- img
        |   +-- js
        +-- config.ini
        +-- content
        |   +-- pages
        |   |   +-- *.md
        +-- layouts
        |   +-- *.html
        +-- router.php

#### _config.ini_

Website configuration file:

##### Site
| Setting           | Description                                    |
| ----------------- | ---------------------------------------------- |
| ```name```        | The name of your website                       |
| ```baseline```    | The baseline of your website                   |
| ```description``` | The description of your website                |
| ```base_url```    | The URL of your website                        |
| ```language```    | The Language of your website (Use IETF format) |

##### Author
| Setting           | Description                                    |
| ----------------- | ---------------------------------------------- |
| ```name```        | Your name                                      |
| ```email```       | Your e-mail address                            |
| ```home```        | The URL of your own website                    |

##### Deploy
| Setting           | Description                                    |
| ----------------- | ---------------------------------------------- |
| ```repository```  | The URL of the GitHub repository               |
| ```branch```      | The target branch name                         |

#### _layouts_

Layouts folder: PHPoole use [Twig](http://twig.sensiolabs.org) layouts (```default.html``` by default) to generate static HTML files.

#### _assets_

Assets folder: CSS, Javascript, images, fonts, etc.

#### _content_

Content folder: Where you can put your content (pages in [Markdown](http://daringfireball.net/projects/markdown/) format).


### Generate

Run the following command to generate your static website.

    $ php phpoole.phar [folder] --generate

Alias: ```$ php phpoole.phar [folder] -g```

After ```--generate```, here's how the folder looks like:

    [folder]
    +-- _phpoole
    |   +-- [...]
    +-- assets
    |   +-- css
    |   +-- img
    |   +-- js
    +-- *.html
    +-- README.md


### Serve

Run the following command to launch the built-in server to test your website before deployment.

    $ php phpoole.phar [folder] --serve

Alias: ```$ php phpoole.phar [folder] -s```

Then browse [http://localhost:8000](http://localhost:8000).

You can chain options. For example, if you want to generate then serve:
```$ php phpoole.phar [folder] -gs```


### Deploy

Run the following command to deploy your website.

    $ php phpoole.phar [folder] --deploy

Alias: ```$ php phpoole.phar [folder] -d```

After ```--deploy```, a "cached copy" of ```[folder]``` is created at the same level: ```[.folder]```.

You can chain options. For example, if you want to generate then deploy:
```$ php phpoole.phar [folder] -gd```

Note: This feature requires [Git](http://git-scm.com) and a [GitHub](https://github.com) account.
