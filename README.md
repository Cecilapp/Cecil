*_Branch 1.5.0-dev_*

**PHPoole** is a light and easy static website generator written in PHP.
It takes your content (written in [Markdown](http://daringfireball.net/projects/markdown/) plain text format), merges it with layouts ([Twig](http://twig.sensiolabs.org/) templates) and generates static HTML files.

**Q/A:**

* Why the name _PHPoole_? It is [PHP](http://www.php.net) + [Poole](http://en.wikipedia.org/wiki/Strange_Case_of_Dr_Jekyll_and_Mr_Hyde#Mr._Poole) (in reference to [Jekyll Ruby](http://jekyllrb.com))
* Is PHPoole is stable? Not really, be careful!
* Is there a demo? Yes there is, see [PHPoole/demo](https://github.com/PHPoole/demo)
* How to get support? Through [GitHub issues](https://github.com/Narno/PHPoole/issues) system
* Can I contribute? Yes you could submit a [Pull Request](https://help.github.com/articles/using-pull-requests) on [GitHub](https://github.com/Narno/PHPoole)


Quick Start
-----------

### 1. Get PHPoole
    $ curl -SO http://phpoole.narno.org/downloads/phpoole.phar

### 2. Initialize a new website
    $ php phpoole.phar init

### 3. Build the website
    $ php phpoole.phar build

### 4. Serve the local website
    $ php phpoole.phar serve

----

Requirements
------------

Please see the [composer.json](https://github.com/Narno/PHPoole/blob/1.5.0-dev/composer.json) file.


Usage
-----

### Get PHPoole
    
    $ curl -SO http://phpoole.narno.org/downloads/phpoole.phar


### Initialize

Once PHPoole is downloaded, run the following command to build all files you need (in the current or target folder).

    $ php phpoole.phar init [folder]

Note: You can force initialization of an already initialized folder.

    $ php phpoole.phar init [folder] --force

After ```init```, here's how the folder looks like:

    [folder]
    +-- content
    |   +-- *.md
    +-- layouts
    |   +-- *.html
    +-- static
    +-- config.ini
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

#### _layouts_

Layouts folder: PHPoole use [Twig](http://twig.sensiolabs.org) layouts (```default.html``` by default) to generate static HTML files.

#### _content_

Content folder: Where you can put your content (pages in [Markdown](http://daringfireball.net/projects/markdown/) format).

#### _static_

Static files.


### Build

Run the following command to build the website.

    $ php phpoole.phar build [folder]

After ```build```, here's how the folder looks like:

    [folder]
    +-- [...]
    +-- site
        +-- *.html
        +-- README.md


### Serve

Run the following command to launch the built-in server to test your website before deployment.

    $ php phpoole.phar serve [folder]

Then browse [http://localhost:8000](http://localhost:8000).

If you want to build then serve:
```$ php phpoole.phar build [folder] --serve```
