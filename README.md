PHPoole
=======

PHPoole is (will be! _work in progress_) a simple static website/weblog generator written in PHP.
It parses your content written with Markdown, merge it with layouts and generates static HTML files.

PHPoole = [PHP](http://www.php.net) + [Poole](http://en.wikipedia.org/wiki/Strange_Case_of_Dr_Jekyll_and_Mr_Hyde#Mr._Poole)

Requirements
------------

* PHP 5.3+
* ZF2 Components: Loader, Console
* Twig

Quick Start
-----------

###1. Install
```
$ git clone https://github.com/Narno/PHPoole.git && cd PHPoole
$ curl -s https://getcomposer.org/installer | php
$ php composer.phar install
```
```
$ sudo ln -s phpoole /usr/bin/phpoole
```

###2. Setup
```
$ mkdir mywebsite
$ phpoole --init mywebsite
```

###3. Generate
```
$ phpoole --generate mywebsite
```

###4. Deploy
```
$ phpoole --deploy mywebsite
```

----

Setup
-----

Once PHPoole is installed, run the following command to build all files you need (in the curent or target folder).

```
$ phpoole --init [folder]
```

After ```--init```, here's how the folder looks like:
```
[folder]
└── .phpoole
    ├── config.ini
    ├── layouts
    |   └── base.html
    ├── assets
    |   └── css
    |   ├── img
    |   └── js
    └── content
        ├── posts
        └── page
            └── index.md
```

### config.ini

Website configuration file.

### layouts

Layouts folder: PHPoole generate static file based on layout.

### assets

Assets folder: CSS and Javascript files, images, etc.

### content

Content folder: Where you can put your content (posts and pages as markdown files).


Generate
--------

Run the following command to generate your static website.

```
$ phpoole --generate [folder]
$ phpoole -g [folder]
```

After ```--generate```, here's how the folder looks like:
```
[folder]
├── .phpoole
|   └── <...>
├── assets
|   ├── css
|   ├── img
|   └── js
└── index.html
```


Deploy
------

Run the following command to deploy your website.

```
$ phpoole --deploy [folder]
$ phpoole -d [folder]
```

Deploys after generating:
```
$ phpoole -gd [folder]
```
