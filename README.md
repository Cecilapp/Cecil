PHPoole
=======

PHPoole is a light and easy static website / blog generator written in PHP.
It takes your content (written in [Markdown](http://daringfireball.net/projects/markdown/) plain text format), merges it with layouts ([Twig](http://twig.sensiolabs.org/) templates) and generates static HTML files.

* Why _PHPoole_ name ? It is [PHP](http://www.php.net) + [Poole](http://en.wikipedia.org/wiki/Strange_Case_of_Dr_Jekyll_and_Mr_Hyde#Mr._Poole)
* There is a demo? Yes, see [PHPoole-demo](https://github.com/Narno/PHPoole-demo)
* What's the progress status? Still in beta!

Requirements
------------

* [PHP](https://github.com/php) 5.3+ (5.4+ to use ```--serve``` option)
* [Zend Console](https://github.com/zendframework/Component_ZendConsole) (+ Zend Stdlib)
* [PHP Markdown](https://github.com/michelf/php-markdown)
* [Twig](https://github.com/fabpot/Twig)

Quick Start
-----------

###1. Install
```
$ git clone https://github.com/Narno/PHPoole.git && cd PHPoole
$ curl -s https://getcomposer.org/installer | php
$ php composer.phar install
```

###2. Setup a new website
```
$ mkdir mywebsite
$ phpoole.php -i mywebsite
```

###3. Generate static files
```
$ phpoole.php -g mywebsite
```

###4. Serve to test on local
```
$ phpoole.php -s mywebsite
```

###5. Deploy on GitHub Pages
```
$ phpoole.php -d mywebsite
```

----


Installation
------------

Use [Git](http://git-scm.com) and [Composer](http://getcomposer.org) to easily install PHPoole.

```
$ git clone https://github.com/Narno/PHPoole.git && cd PHPoole
```
```
$ curl -s https://getcomposer.org/installer | php
$ php composer.phar install
```


Setup
-----

Once PHPoole is installed, run the following command to build all files you need (in the curent or target folder).

```
$ phpoole.php --init <folder>
```
Alias: ```$ phpoole.php -i <folder>```

After ```--init```, here's how the folder looks like:
```
<folder>
└── _phpoole
    ├── assets
    |   ├── css
    |   ├── img
    |   └── js
    ├── config.ini
    ├── content
    |   ├── pages
    |   |   └── index.md
    |   └── posts
    ├── layouts
    |   └── base.html
    └── router.php
```

### _config.ini_

Website configuration file:

#### Site
| Setting           | Description                                    |
| ----------------- | ---------------------------------------------- |
| ```name```        | The name of your website                       |
| ```baseline```    | The baseline of your website                   |
| ```description``` | The description of your website                |
| ```base_url```    | The URL of your website                        |
| ```language```    | The Language of your website (Use IETF format) |

#### Author
| Setting           | Description                                    |
| ----------------- | ---------------------------------------------- |
| ```name```        | Your name                                      |
| ```email```       | Your e-mail address                            |
| ```home```        | The URL of your own website                    |

#### Deploy
| Setting           | Description                                    |
| ----------------- | ---------------------------------------------- |
| ```repository```  | The URL of the GitHub repository               |

### _layouts_

Layouts folder: PHPoole generate static file based on layouts (```base.html``` by default).

### _assets_

Assets folder: CSS, Javascript, images, etc.

### _content_

Content folder: Where you can put your content (posts and pages as markdown files).


Generate
--------

Run the following command to generate your static website.

```
$ phpoole.php --generate <folder>
```
Alias: ```$ phpoole.php -g <folder>```

After ```--generate```, here's how the folder looks like:
```
<folder>
├── _phpoole
|   └── [...]
├── assets
|   ├── css
|   ├── img
|   └── js
└── index.html
```


Serve
-----

Run the following command to launch the built-in server to test your website before deployment.

```
$ phpoole.php --server <folder>
```
Alias: ```$ phpoole.php -s <folder>```

Then browse [http://localhost:8000](http://localhost:8000).


Deploy
------

Run the following command to deploy your website.

```
$ phpoole.php --deploy <folder>
```
Alias: ```$ phpoole.php -d <folder>```

After ```--deploy```, here's how the folder looks like:
```
<folder>
└── [...]
<.folder>
├── .git
└── [...]
```
