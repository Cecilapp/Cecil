<?php

// PHP built-in server router
date_default_timezone_set('UTC');
define('DIRECTORY_INDEX', 'index.html');
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$ext = pathinfo($path, PATHINFO_EXTENSION);
if ($path == '/watcher') {
    http_response_code(200);
    if (!file_exists($_SERVER['DOCUMENT_ROOT'].'/../.phpoole/watch.flag')) {
        echo 'stop';
        exit();
    }
    if (file_exists($_SERVER['DOCUMENT_ROOT'].'/../.phpoole/changes.flag')) {
        echo 'true';
        unlink($_SERVER['DOCUMENT_ROOT'].'/../.phpoole/changes.flag');
    } else {
        echo 'false';
    }
    exit();
}
if (empty($ext)) {
    $pathname = rtrim($path, '/').'/'.DIRECTORY_INDEX;
} else {
    $pathname = $path;
}
if (file_exists($_SERVER['DOCUMENT_ROOT'].$pathname)) {
    $ext = pathinfo($pathname, PATHINFO_EXTENSION);
    if ($ext == 'html') {
        $html = file_get_contents($_SERVER['DOCUMENT_ROOT'].$pathname);
        // includes "live reload" script in HTML files
        if (file_exists($_SERVER['DOCUMENT_ROOT'].'/../.phpoole/watch.flag')) {
            $script = file_get_contents(__DIR__.'/livereload.js');
            $html = str_replace('</body>', $script."\n".'</body>', $html);
        }
        // replaces base url by localhost
        $baseurl = trim(file_get_contents($_SERVER['DOCUMENT_ROOT'].'/../.phpoole/baseurl'));
        if (false !== strstr($baseurl, 'http') || $baseurl != '/') {
            $html = str_replace($baseurl, 'http://localhost:8000/', $html);
        }
        echo $html;

        return true;
    }

    return false;
}
http_response_code(404);
echo '404, page not found';
