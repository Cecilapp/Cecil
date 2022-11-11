<?php

declare(strict_types=1);

/*
 * This file is part of Cecil.
 *
 * Copyright (c) Arnaud Ligny <arnaud@ligny.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

// Router for the PHP built-in server
// phpcs:disable PSR1.Files.SideEffects

if (!date_default_timezone_get()) {
    date_default_timezone_set('UTC');
}
mb_internal_encoding('UTF-8');

define('SERVER_TMP_DIR', '.cecil');
define('DIRECTORY_INDEX', 'index.html');
define('FILE_404', '404.html');
$isIndex = null;
$mediaSubtypeText = ['plain', 'css', 'javascript', 'json', 'xml', 'csv'];

$path = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));

// watcher (called by 'livereload.js')
if ($path == '/watcher') {
    header("Content-Type: text/event-stream\n\n");
    header('Cache-Control: no-cache');
    header('Access-Control-Allow-Origin: *');
    $flagFile = $_SERVER['DOCUMENT_ROOT'].'/../'.SERVER_TMP_DIR.'/changes.flag';
    if (file_exists($flagFile)) {
        echo "event: reload\n";
        printf("data: %s\n\n", file_get_contents($flagFile));
        unlink($flagFile);
    }
    exit();
}

// path '/' = '/index.html'
if (substr($path, -1) == '/') {
    $isIndex = true;
    $path = rtrim($path, '/').'/'.DIRECTORY_INDEX;
}

// file absolute path
$filename = $_SERVER['DOCUMENT_ROOT'].$path;

// HTTP response: 404
if (!file_exists($filename)) {
    http_response_code(404);
    // favicon.ico
    if ($path == '/favicon.ico') {
        header('Content-Type: image/vnd.microsoft.icon');

        return logger(false);
    }
    // 404.html exists?
    if (!$isIndex || !file_exists($_SERVER['DOCUMENT_ROOT'].'/'.FILE_404)) {
        echo 'Error 404: page or file not found.';

        return logger(true);
    }
    $path = '/'.FILE_404;
    $filename = $_SERVER['DOCUMENT_ROOT'].'/'.FILE_404;
}

// HTTP response: 200
$content = file_get_contents($filename);
$pathInfo = getPathInfo($path);
// text content
if (in_array($pathInfo['media_maintype'], $mediaSubtypeText)) {
    // replaces the "live" baseurl by the "local" baseurl
    $baseurl = explode(';', trim(file_get_contents($_SERVER['DOCUMENT_ROOT'].'/../'.SERVER_TMP_DIR.'/baseurl')));
    if (strstr($baseurl[0], 'http') !== false || $baseurl[0] != '/') {
        $content = str_replace($baseurl[0], $baseurl[1], $content);
    }
    // HTML content: injects live reload script
    if ($pathInfo['media_subtype'] == 'html') {
        if (file_exists(__DIR__.'/livereload.js')) {
            $script = file_get_contents(__DIR__.'/livereload.js');
            $content = str_ireplace('</body>', "  <script>$script    </script>\n  </body>", $content);
            if (stristr($content, '</body>') === false) {
                $content .= "\n$script";
            }
        }
    }
}

// returns result
header('Etag: '.md5_file($filename));
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');
header('X-Powered-By: Cecil,PHP/'.phpversion());
foreach ($pathInfo['headers'] as $header) {
    header($header);
}
echo $content;

return logger(true);

/*
 * Functions
 */

// logger + return
function logger(bool $return): bool
{
    \error_log(
        \sprintf("%s:%d [%d]: %s\n", $_SERVER['REMOTE_ADDR'], $_SERVER['REMOTE_PORT'], \http_response_code(), $_SERVER['REQUEST_URI']),
        3,
        $_SERVER['DOCUMENT_ROOT'].'/../'.SERVER_TMP_DIR.'/server.log'
    );

    return $return;
}

// get path info (media type + headers)
function getPathInfo(string $path): array
{
    $filename = $_SERVER['DOCUMENT_ROOT'].$path;
    $extension = pathinfo($path, PATHINFO_EXTENSION);
    $mediaType = \mime_content_type($filename); // e.g.: "text/html"
    $info = [
        'media_maintype' => explode('/', $mediaType)[0], // e.g.: "text"
        'media_subtype'  => explode('/', $mediaType)[1], // e.g.: "html"
    ];
    $info['headers'] = [
        "Content-Type: {$info['media_maintype']}/{$info['media_subtype']}",
    ];
    // forces the info according to the extension
    switch ($extension) {
        case 'htm':
        case 'html':
            $info = [
                'media_maintype' => 'text',
                'media_subtype'  => 'html',
                'headers'        => [
                    'Content-Type: text/html; charset=utf-8',
                ],
            ];
            break;
        case 'css':
            $info['headers'] = [
                'Content-Type: text/css',
            ];
            break;
        case 'js':
            $info['headers'] = [
                'Content-Type: application/javascript',
            ];
            break;
        case 'svg':
            $info['headers'] = [
                'Content-Type: image/svg+xml',
            ];
            break;
        case 'xml':
            $info['headers'] = [
                'Content-Type: application/xml; charset=utf-8',
                'X-Content-Type-Options: nosniff',
            ];
            break;
        case 'xsl':
            $info['headers'] = [
                'Content-Type: application/xslt+xml',
            ];
            break;
    }
    // forces the info according to the media main type
    switch ($info['media_maintype']) {
        case 'audio':
            $info['headers'] += [
                'Content-Transfer-Encoding: binary',
                'Content-Length: '.filesize($filename),
                'Accept-Ranges: bytes',
            ];
            break;
    }

    return $info;
}
