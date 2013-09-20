PHPoole
=======

PHPoole is (will be, _work in progress_) a simple static website/weblog generator written in PHP.
It parses your content written with Markdown, merge it with layouts and generates static HTML files.

PHPoole = [PHP](http://www.php.net) + [Poole](http://en.wikipedia.org/wiki/Strange_Case_of_Dr_Jekyll_and_Mr_Hyde#Mr._Poole)

Requirements
------------

* PHP 5.3+ (5.4+ to use ```--serve``` option)
* ZF2 Components: Console (+ Stdlib)
* PHP Markdown
* Twig

Quick Start
-----------

###1. Install
```
$ git clone https://github.com/Narno/PHPoole.git && cd PHPoole
$ curl -s https://getcomposer.org/installer | php
$ php composer.phar install
```

###2. Setup
```
$ mkdir mywebsite
$ phpoole.php --init mywebsite
```

###3. Generate
```
$ phpoole.php --generate mywebsite
```

###4. Serve
```
$ phpoole.php --serve mywebsite
```

###5. Deploy
```
$ phpoole.php --deploy mywebsite
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
├── .phpoole
|   ├── assets
|   |   ├── css
|   |   ├── img
|   |   └── js
|   ├── config.ini
|   ├── content
|   |   ├── pages
|   |   |   └── index.md
|   |   └── posts
|   └── layouts
|       └── base.html
└── router.php
```

### config.ini

Website configuration file.

### layouts

Layouts folder: PHPoole generate static file based on layouts (```base.html``` by default).

### assets

Assets folder: CSS and Javascript files, images, etc.

### content

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
├── .phpoole
|   └── [...]
├── assets
|   ├── css
|   ├── img
|   └── js
├── index.html
└── router.php
```


Serve
-----

Run the following command to launch the built-in server and to test your website before deployment.

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

Deploys after generating:
```
$ phpoole.php -gd <folder>
```

Note: Not yet implemented.
