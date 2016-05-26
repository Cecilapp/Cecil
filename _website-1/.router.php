<?php
// PHP built-in server router
date_default_timezone_set('UTC');
define('DIRECTORY_INDEX', 'index.html');
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$ext = pathinfo($path, PATHINFO_EXTENSION);
if ($path == '/watcher') {
    http_response_code(200);
    if (!file_exists(__DIR__.'/.watch')) {
        echo 'stop';
        exit();
    }
    if (file_exists(__DIR__.'/.changes')) {
        echo 'true';
        unlink(__DIR__.'/.changes');
    } else {
        echo 'false';
    }
    exit();
}
if (empty($ext)) {
    $path = rtrim($path, '/').'/'.DIRECTORY_INDEX;
}
if (file_exists($_SERVER['DOCUMENT_ROOT'].$path)) {
    $ext = pathinfo($path, PATHINFO_EXTENSION);
    if ($ext == 'html') {
        $html = file_get_contents($_SERVER['DOCUMENT_ROOT'].$path);
        // includes "live relaod" script in HTML files
        $script = file_get_contents(__DIR__ . '/.watch.js');
        $html = str_replace('</body>', $script."\n".'</body>', $html);
        // replaces base url by localhost
        $html = str_replace(trim(file_get_contents(__DIR__.'/.baseurl')), 'http://localhost:8000/', $html);
        echo $html;

        return true;
    }

    return false;
}
http_response_code(404);
echo '404, page not found';
