<?php
/*
 * This file is part of the Cecil/Cecil package.
 *
 * Copyright (c) Arnaud Ligny <arnaud@ligny.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

// Router for the PHP built-in server
// phpcs:disable PSR1.Files.SideEffects

date_default_timezone_set('UTC');
define('SERVER_TMP_DIR', '.cecil');
define('DIRECTORY_INDEX', 'index.html');
define('DEBUG', false);

$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$ext = pathinfo($path, PATHINFO_EXTENSION);

// watcher, called by livereload.js
if ($path == '/watcher') {
    header("Content-Type: text/event-stream\n\n");
    header('Cache-Control: no-cache');
    header('Access-Control-Allow-Origin: *');
    $flagFile = $_SERVER['DOCUMENT_ROOT'].'/../'.SERVER_TMP_DIR.'/changes.flag';
    if (file_exists($flagFile)) {
        echo "event: reload\n";
        printf('data: %s', file_get_contents($flagFile));
    }
    echo "\n\n";
    exit();
}
// ie: /blog/post-1/ -> /blog/post-1/index.html
if (empty($ext)) {
    $pathname = rtrim($path, '/').'/'.DIRECTORY_INDEX;
// ie: /css/style.css
} else {
    $pathname = $path;
}
if (file_exists($filename = $_SERVER['DOCUMENT_ROOT'].$pathname)) {
    $ext = pathinfo($pathname, PATHINFO_EXTENSION);
    $mimeshtml = ['xhtml+xml', 'html'];
    $mimestxt = ['json', 'xml', 'css', 'csv', 'javascript', 'plain', 'text'];

    // get file mime type
    if (!extension_loaded('fileinfo')) {
        http_response_code(500);
        echo "The extension 'fileinfo' must be enabled in your 'php.ini' file!";
    }
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimetype = finfo_file($finfo, $filename);
    $mime = explode('/', $mimetype)[1];
    finfo_close($finfo);

    // manipulate html and plain text file content for local serve
    if (in_array($mime, $mimeshtml) || in_array($mime, $mimestxt)) {
        $content = file_get_contents($filename);
        // html only
        if (in_array($mime, $mimeshtml)) {
            // inject live reload script
            if (file_exists(__DIR__.'/livereload.js')) {
                $script = file_get_contents(__DIR__.'/livereload.js');
                $content = str_replace('</body>', "$script\n  </body>", $content);
            }
        }
        // replace the "prod" baseurl by the "local" baseurl
        $baseurls = trim(file_get_contents($_SERVER['DOCUMENT_ROOT'].'/../'.SERVER_TMP_DIR.'/baseurl'));
        $baseurls = explode(';', $baseurls);
        $baseurl = $baseurls[0];
        $baseurlLocal = $baseurls[1];
        if (false !== strstr($baseurl, 'http') || $baseurl != '/') {
            $content = str_replace($baseurl, $baseurlLocal, $content);
        }
        // return result
        header('Etag: '.md5_file($filename));
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Cache-Control: post-check=0, pre-check=0', false);
        header('Pragma: no-cache');
        header('Content-Type: '.$mimetype);
        if ($ext == 'css') {
            header('Content-Type: text/css');
        }
        if ($ext == 'js') {
            header('Content-Type: application/javascript');
        }
        if ($ext == 'svg') {
            header('Content-Type: image/svg+xml');
        }
        echo $content;

        return true;
    }

    return false;
}
http_response_code(404);
echo '404, page not found';
