PHPoole
=======

PHPoole is (will be!) a simple static website/weblog generator written in PHP.
It parses your content written with Markdown, merge it with layouts and generates static HTML files.

Requirements
-------------------

* PHP 5.3+
* PHP 5.4+ for the ```--serve``` option (PHP internal server)

Quick Start
-----------

###1. Install
```
$ git clone https://github.com/Narno/PHPoole.git && cd PHPoole
$ curl -s https://getcomposer.org/installer | php
$ php composer.phar install
$ sudo ln -s phpoole /usr/bin/phpoole
```

###2. Setup
```
$ mkdir mywebsite && cd mywebsite
$ phpoole --init
```

###3. Generate
```
$ phpoole --generate
```

###4. Serve
```
$ phpoole --serve
```

###5. Deploy
```
$ phpoole --deploy
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
<mywebsite>
└── .phpoole
    ├── config.ini
    ├── layouts
    |   └── base.php
    ├── assets
    |   └── css
    |   |   └── style.css
    |   ├── img
    |   └── js
    ├── content
    |   ├── posts
    |   └── page
    |       └── index.md
    └── robots.txt
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
$ phpoole --generate
$ phpoole -g
```

After ```--generate```, here's how the folder looks like:
```
<mywebsite>
└── .phpoole
|   └── ...
├── assets
|   ├── css
|   |   └── style.css
|   ├── img
|   └── js
├── index.html
└── robots.txt
```

Deploys after generating:
```
$ phpoole --generate --deploy
$ phpoole -gd
```


Serve
-----

Run the following command to start [local PHP server](http://php.net/manual/en/features.commandline.webserver.php) and check your website before deploy.

```
$ phpoole --serve
$ phpoole -s
```

Your website will running at http://localhost:8000. You can edit the server port in config.ini or use ```-p``` flag to override the default port.
```
$ phpoole --serve -p 6969
```


Deploy
------

Run the following command to deploy your website.

```
$ phpoole --deploy
$ phpoole -d
```
