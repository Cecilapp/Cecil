PHPoole is a light and easy static website / blog generator written in PHP.
It takes your content (written in [Markdown](http://daringfireball.net/projects/markdown/) plain text format), merges it with layouts ([Twig](http://twig.sensiolabs.org/) templates) and generates static HTML files.

**Q/A:**

* Why the name _PHPoole_? It is [PHP](http://www.php.net) + [Poole](http://en.wikipedia.org/wiki/Strange_Case_of_Dr_Jekyll_and_Mr_Hyde#Mr._Poole) (en référence à [Jekyll Ruby](http://jekyllrb.com))
* Is PHPoole is stable? It is still in beta, be careful!
* Is there a demo? Yes there is, see [PHPoole-demo](https://github.com/Narno/PHPoole-demo)
* How to get support? Through [GitHub issues](https://github.com/Narno/PHPoole/issues) system
* Can I contribute? Yes you could submit a [Pull Request](https://help.github.com/articles/using-pull-requests) on [GitHub](https://github.com/Narno/PHPoole)

Requirements
------------

* [PHP](https://github.com/php) 5.3+ (5.4+ to use ```--serve``` option)
* [ZF2 components](https://github.com/zendframework)
* [PHP Markdown](https://github.com/michelf/php-markdown)
* [Twig](https://github.com/fabpot/Twig)
* [Git](http://git-scm.com) (to deploy on GitHub Pages)

Quick Start
-----------

### 1. Install
    $ git clone https://github.com/Narno/PHPoole.git && cd PHPoole
    $ curl -s https://getcomposer.org/installer | php
    $ php composer.phar install

### 2. Setup a new website
    $ mkdir mywebsite
    $ php phpoole.php mywebsite --init

### 3. Generate static files
    $ php phpoole.php mywebsite --generate

### 4. Serve to test on local
    $ php phpoole.php mywebsite --serve

### 5. Deploy on GitHub Pages
    $ php phpoole.php mywebsite --deploy

----

Usage
-----

### Installation

Use [Git](http://git-scm.com) and [Composer](http://getcomposer.org) to easily install PHPoole.

    $ git clone https://github.com/Narno/PHPoole.git && cd PHPoole
    $ curl -s https://getcomposer.org/installer | php
    $ php composer.phar install


### Setup

Once PHPoole is installed, run the following command to build all files you need (in the curent or target folder).

    $ php phpoole.php [folder] --init

With [Twitter Bootstrap](http://getbootstrap.com) layout (and assets):

    $ php phpoole.php [folder] --init=bootstrap

Alias: ```$ php phpoole.php [folder] -i```

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
        |   |   +-- index.md
        |   +-- posts
        +-- layouts
        |   +-- base.html
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

#### _layouts_

Layouts folder: PHPoole generate static file based on layouts (```base.html``` by default).

#### _assets_

Assets folder: CSS, Javascript, images, etc.

#### _content_

Content folder: Where you can put your content (pages and posts in markdown format).


### Generate

Run the following command to generate your static website.

    $ php phpoole.php [folder] --generate

Alias: ```$ php phpoole.php [folder] -g```

After ```--generate```, here's how the folder looks like:

    [folder]
    +-- _phpoole
    |   +-- [...]
    +-- assets
    |   +-- css
    |   +-- img
    |   +-- js
    +-- index.html


### Serve

Run the following command to launch the built-in server to test your website before deployment.

    $ php phpoole.php [folder] --serve

Alias: ```$ php phpoole.php [folder] -s```

Then browse [http://localhost:8000](http://localhost:8000).

You can chain options. For example, if you want to generate then serve:
```$ php phpoole.php [folder] -gs```


### Deploy

Run the following command to deploy your website.

    $ php phpoole.php [folder] --deploy

Alias: ```$ php phpoole.php [folder] -d```

After ```--deploy```, a cached copy of ```[folder]``` is created at the same level: ```[.folder]```.

You can chain options. For example, if you want to generate then deploy:
```$ php phpoole.php [folder] -gd```
