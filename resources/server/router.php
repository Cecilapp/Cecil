<?php

/**
 * This file is part of Cecil.
 *
 * (c) Arnaud Ligny <arnaud@ligny.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

// Router for the PHP built-in server
// phpcs:disable PSR1.Files.SideEffects

if (!date_default_timezone_get()) {
    date_default_timezone_set('UTC');
}
mb_internal_encoding('UTF-8');

\define('DIRECTORY_INDEX', '/index.html');
\define('ERROR_404', '/404.html');
$mediaSubtypeText = ['javascript', 'xml', 'json', 'ld+json', 'csv'];

$path = htmlspecialchars(urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH)));

// watcher (called by `livereload.js`)
if ($path == '/watcher') {
    header("Content-Type: text/event-stream\n\n");
    header('Cache-Control: no-cache');
    header('Access-Control-Allow-Origin: *');
    $flagFile = __DIR__ . '/changes.flag';
    if (file_exists($flagFile)) {
        echo "event: reload\n";
        printf("data: %s\n\n", file_get_contents($flagFile));
        unlink($flagFile);
    }
    exit;
}

// `path` or `path/` are converted to `path/index.html`
if ((empty(pathinfo($path, PATHINFO_EXTENSION)) || $path[-1] == '/') && file_exists($_SERVER['DOCUMENT_ROOT'] . rtrim($path, '/') . DIRECTORY_INDEX)) {
    $path = rtrim($path, '/') . DIRECTORY_INDEX;
}

// file absolute path
$filename = $_SERVER['DOCUMENT_ROOT'] . $path;

// HTTP response: 404
if ((realpath($filename) === false || strpos(realpath($filename), realpath($_SERVER['DOCUMENT_ROOT'])) !== 0) || !file_exists($filename) || is_dir($filename)) {
    http_response_code(404);

    // favicon.ico
    if ($path == '/favicon.ico') {
        header('Content-Type: image/vnd.microsoft.icon');

        return logger(false);
    }

    // is 404.html exists?
    $path404 = '';
    $subDir = '';
    if (file_exists($_SERVER['DOCUMENT_ROOT'] . ERROR_404)) {
        $path404 = ERROR_404;
    }
    // is 404.html exists in a (language) sub dir?
    $pathAsArray = explode('/', $path);
    if (\count($pathAsArray) > 2) {
        $subDir = '/' . $pathAsArray[1];
        if (file_exists($_SERVER['DOCUMENT_ROOT'] . $subDir . ERROR_404)) {
            $path404 = $subDir . ERROR_404;
        }
    }

    // default 404 page
    if (empty($path404)) {
        echo <<<END
        <!doctype html>
        <html>
            <head>
                <title>404 Not Found</title>
                <style>
                    html { font-family: ui-sans-serif, system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, "Noto Sans", sans-serif, "Apple Color Emoji", "Segoe UI Emoji", "Segoe UI Symbol", "Noto Color Emoji"; }
                    body { background-color: #fcfcfc; color: #333333; margin: 0; padding:0; }
                    h1 { font-size: 1.5em; font-weight: normal; background-color: #eeeeee; min-height:2em; line-height:2em; border-bottom: 1px inset #d6d6d6; margin: 0; }
                    h1, p { padding-left: 10px; }
                    code.url { background-color: #eeeeee; font-family:monospace; padding:0 2px; }
                </style>
                <meta http-equiv="refresh" content="2;URL=$path">
            </head>
            <body>
                <h1>Not Found</h1>
                <p>The requested resource <code class="url">$path</code> was not found on this server.</p>
            </body>
        </html>
        END;

        return logger(true);
    }

    $path = $path404;
    $filename = $_SERVER['DOCUMENT_ROOT'] . $path404;
}

// HTTP response: 200
$content = file_get_contents($filename);
$pathInfo = getPathInfo($path);
// text content
if ($pathInfo['media_maintype'] == 'text' || \in_array($pathInfo['media_subtype'], $mediaSubtypeText)) {
    // HTML content: injects live reload script
    if ($pathInfo['media_subtype'] == 'html') {
        if (file_exists(__DIR__ . '/livereload.js')) {
            $script = file_get_contents(__DIR__ . '/livereload.js');
            $content = str_ireplace('</body>', "  <script>$script    </script>\n  </body>", $content);
            if (stristr($content, '</body>') === false) {
                $content .= "\n<script>$script    </script>";
            }
        }
    }
}

/**
 * Returns result
 */

// local server headers
header('Etag: ' . md5_file($filename));
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Cache-Control: post-check=0, pre-check=0', false);
header('Pragma: no-cache');
header('X-Powered-By: Cecil,PHP/' . phpversion());
// file type headers
foreach ($pathInfo['headers'] as $header) {
    header($header);
}
// custom headers based on path
$headersFile = __DIR__ . '/headers.ini';
if (file_exists($headersFile)) {
    $headersArray = parse_ini_file($headersFile, true);
    // path with wildcard
    foreach ($headersArray as $source => $headers) {
        $match = sscanf($path, str_replace('*', '%s', $source));
        if (!empty($match) && $match[0] !== null) {
            foreach ($headers as $key => $value) {
                header("$key: $value");
            }
        }
    }
    // exact file path
    if (\array_key_exists($path, $headersArray)) {
        foreach ($headersArray[$path] as $key => $value) {
            header("$key: $value");
        }
    }
}
// file content
echo $content;

return logger(true);

/*
 * Functions
 */

// logger + return
function logger(bool $return): bool
{
    error_log(
        \sprintf("%s:%d [%d]: %s\n", $_SERVER['REMOTE_ADDR'], $_SERVER['REMOTE_PORT'], http_response_code(), $_SERVER['REQUEST_URI']),
        3,
        __DIR__ . '/server.log'
    );

    return $return;
}

// get path info (media type + headers)
function getPathInfo(string $path): array
{
    $filename = $_SERVER['DOCUMENT_ROOT'] . $path;
    $mediaType = mime_content_type($filename); // e.g.: "text/html"
    $info = [
        'media_maintype' => explode('/', $mediaType)[0], // e.g.: "text"
        'media_subtype'  => explode('/', $mediaType)[1], // e.g.: "html"
    ];
    $info['headers'] = [
        "Content-Type: {$info['media_maintype']}/{$info['media_subtype']}",
    ];
    // forces info according to the extension
    switch (pathinfo($path, PATHINFO_EXTENSION)) {
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
            $info = [
                'media_maintype' => 'application',
                'media_subtype'  => 'javascript',
                'headers'        => [
                    'Content-Type: application/javascript',
                ],
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
        case 'yml':
        case 'yaml':
            $info['headers'] = [
                'Content-Type: application/yaml',
            ];
            break;
    }
    // forces info according to the media main type
    switch ($info['media_maintype']) {
        case 'video':
        case 'audio':
            $info['headers'] += [
                'Content-Transfer-Encoding: binary',
                'Content-Length: ' . filesize($filename),
                'Accept-Ranges: bytes',
            ];
            break;
    }

    return $info;
}
