PHPoole
=======

PHPoole is (will be!) a simple static website/weblog generator written in PHP.
It parses your content written with Markdown, merge it with layouts and generates static HTML files.

Requirements
-------------------

* PHP 5.3+
* PHP 5.4+ for the ```--serve``` command (PHP internal server)

Quick Start
-----------

###1. Install
```
$ git clone https://github.com/Narno/PHPoole.git && cd PHPoole
$ curl -s https://getcomposer.org/installer | php
$ php composer.phar install
$ sudo ln -s `pwd`/bin/phpoole /usr/local/bin/phpoole
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

_mywebsite_ folder structure
----------------

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
