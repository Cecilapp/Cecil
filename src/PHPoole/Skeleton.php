<?php
/*
 * This file is part of the PHPoole package.
 *
 * Copyright (c) Arnaud Ligny <arnaud@ligny.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPoole;

use Exception;

/**
 * Class Skeleton
 * @package PHPoole
 */
class Skeleton
{
    /**
     * @param PHPoole $phpoole
     * @return string
     * @throws Exception
     */
    public static function createConfigFile(PHPoole $phpoole)
    {
        $content = <<<'EOT'
[site]
name        = "PHPoole"
baseline    = "Light and easy static website generator!"
description = "PHPoole is a light and easy static website / blog generator written in PHP. It parses your content written with Markdown, merge it with layouts and generates static HTML files."
base_url    = "http://localhost:8000"
language    = "en"
[author]
name  = "Arnaud Ligny"
email = "arnaud+phpoole@ligny.org"
home  = "http://narno.org"
[deploy]
repository = "https://github.com/Narno/PHPoole.git"
branch     = "gh-pages"
EOT;
        if (!@file_put_contents($phpoole->getWebsitePath() . '/' . PHPoole::PHPOOLE_DIRNAME . '/' . PHPoole::CONFIG_FILENAME, $content)) {
            throw new Exception('Cannot create the config file');
        }
        return 'Config file';
    }

    /**
     * @param PHPoole $phpoole
     * @return string
     * @throws Exception
     */
    public static function createLayoutsDir(PHPoole $phpoole)
    {
        if (!@mkdir($phpoole->getWebsitePath() . '/' . PHPoole::PHPOOLE_DIRNAME . '/' . PHPoole::LAYOUTS_DIRNAME)) {
            throw new Exception('Cannot create the layouts directory');
        }
        return 'Layouts directory';
    }

    /**
     * @param PHPoole $phpoole
     * @return string
     * @throws Exception
     */
    public static function createLayoutDefaultFile(PHPoole $phpoole)
    {
        $content = <<<'EOT'
<!DOCTYPE html>
<!--[if IE 8]><html class="no-js lt-ie9" lang="{{ site.language }}"><![endif]-->
<!--[if gt IE 8]><!--><html class="no-js" lang="{{ site.language }}"><!--<![endif]-->
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width">
  <meta name="description" content="{{ site.description }}">
  <title>{{ site.name}} - {{ page.title }}</title>
  <style type="text/css">
    body { font: bold 24px Helvetica, Arial; padding: 15px 20px; color: #ddd; background: #333;}
    a:link {text-decoration: none; color: #fff;}
    a:visited {text-decoration: none; color: #fff;}
    a:active {text-decoration: none; color: #fff;}
    a:hover {text-decoration: underline; color: #fff;}
  </style>
</head>
<body>
  <a href="{{ site.base_url}}"><strong>{{ site.name }}</strong></a><br />
  <em>{{ site.baseline }}</em>
  <hr />
  <p>{{ page.content }}</p>
  <hr />
  <p>Powered by <a href="http://phpoole.narno.org">PHPoole</a>, coded by <a href="{{ site.author.home }}">{{ site.author.name }}</a></p>
</body>
</html>
EOT;
        if (!@file_put_contents($phpoole->getWebsitePath() . '/' . PHPoole::PHPOOLE_DIRNAME . '/' . PHPoole::LAYOUTS_DIRNAME . '/default.html', $content)) {
            throw new Exception('Cannot create the default layout file');
        }
        return 'Default layout file';
    }

    /**
     * @param PHPoole $phpoole
     * @return string
     * @throws Exception
     */
    public static function createAssetsDir(PHPoole $phpoole)
    {
        $subDirList = array(
            PHPoole::ASSETS_DIRNAME,
            PHPoole::ASSETS_DIRNAME . '/css',
            PHPoole::ASSETS_DIRNAME . '/img',
            PHPoole::ASSETS_DIRNAME . '/js',
        );
        foreach ($subDirList as $subDir) {
            if (!@mkdir($phpoole->getWebsitePath() . '/' . PHPoole::PHPOOLE_DIRNAME . '/' . $subDir)) {
                throw new Exception('Cannot create the assets directory');
            }
        }
        return 'Assets directory';
    }

    /**
     * @param PHPoole $phpoole
     * @return string
     * @throws Exception
     */
    public static function createContentDir(PHPoole $phpoole)
    {
        $subDirList = array(
            PHPoole::CONTENT_DIRNAME,
            PHPoole::CONTENT_DIRNAME . '/' . PHPoole::CONTENT_PAGES_DIRNAME,
        );
        foreach ($subDirList as $subDir) {
            if (!@mkdir($phpoole->getWebsitePath() . '/' . PHPoole::PHPOOLE_DIRNAME . '/' . $subDir)) {
                throw new Exception('Cannot create the content directory');
            }
        }
        return 'Content directory';
    }

    /**
     * @param PHPoole $phpoole
     * @return string
     * @throws Exception
     */
    public static function createContentDefaultFile(PHPoole $phpoole)
    {
        $content = <<<'EOT'
<!--
title = Home
layout = default
menu = nav
-->
PHPoole is a light and easy static website / blog generator written in PHP.
It parses your content written with Markdown, merge it with layouts and generates static HTML files.

PHPoole = [PHP](http://www.php.net) + [Poole](http://en.wikipedia.org/wiki/Strange_Case_of_Dr_Jekyll_and_Mr_Hyde#Mr._Poole)

Go to the [dedicated website](http://phpoole.narno.org) for more details.
EOT;
        if (!@file_put_contents($phpoole->getWebsitePath() . '/' . PHPoole::PHPOOLE_DIRNAME . '/' . PHPoole::CONTENT_DIRNAME . '/' . PHPoole::CONTENT_PAGES_DIRNAME . '/index.md', $content)) {
            throw new Exception('Cannot create the default content file');
        }
        return 'Default content file';
    }

    /**
     * @param PHPoole $phpoole
     * @return string
     * @throws Exception
     */
    public static function createRouterFile(PHPoole $phpoole)
    {
        $content = <<<'EOT'
<?php
date_default_timezone_set("UTC");
define("DIRECTORY_INDEX", "index.html");
$path = parse_url($_SERVER["REQUEST_URI"], PHP_URL_PATH);
$ext = pathinfo($path, PATHINFO_EXTENSION);
if (empty($ext)) {
    $path = rtrim($path, "/") . "/" . DIRECTORY_INDEX;
}
if (file_exists($_SERVER["DOCUMENT_ROOT"] . $path)) {
    return false;
}
http_response_code(404);
echo "404, page not found";
EOT;
        if (!@file_put_contents($phpoole->getWebsitePath() . '/' . PHPoole::PHPOOLE_DIRNAME . '/router.php', $content)) {
            throw new Exception('Cannot create the router file');
        }
        return 'Router file';
    }

    /**
     * @param PHPoole $phpoole
     * @return string
     * @throws Exception
     */
    public static function createReadmeFile(PHPoole $phpoole)
    {
        $content = <<<'EOT'
Powered by [PHPoole](http://phpoole.narno.org).
EOT;

        if (is_file($phpoole->getWebsitePath() . '/README.md')) {
            if (!@unlink($phpoole->getWebsitePath() . '/README.md')) {
                throw new Exception('Cannot create the README file');
            }
        }
        if (!@file_put_contents($phpoole->getWebsitePath() . '/README.md', $content)) {
            throw new Exception('Cannot create the README file');
        }
        return 'Create README file';
    }
}