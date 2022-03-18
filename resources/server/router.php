<?php
/**
 * This file is part of the Cecil/Cecil package.
 *
 * Copyright (c) Arnaud Ligny <arnaud@ligny.fr>
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

// watcher (called by livereload.js)
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

// pathname of a file
// ie: /css/style.css
$pathname = $path;
// pathname of an HTML page
// ie: /blog/post-1/ -> /blog/post-1/index.html
if (substr($path, -1) == '/') {
    $pathname = rtrim($path, '/').'/'.DIRECTORY_INDEX;
}

// HTTP response: 200
if (file_exists($filename = $_SERVER['DOCUMENT_ROOT'].$pathname)) {
    $mimesHtml = ['xhtml+xml', 'html'];
    $extHtml = ['htm', 'html'];
    $mimesText = ['json', 'xml', 'css', 'csv', 'javascript', 'plain', 'text'];
    $mimesAudio = ['mpeg', 'x-m4a'];

    $ext = pathinfo($pathname, PATHINFO_EXTENSION);
    $mimeType = mime_content_type($filename);
    $mimeSubtype = explode('/', $mimeType)[1];

    // manipulate HTML (and plain text) file content
    if (in_array($mimeSubtype, $mimesHtml) || in_array($mimeSubtype, $mimesText)) {
        $content = file_get_contents($filename);
        // html only
        if (in_array($mimeSubtype, $mimesHtml) && in_array($ext, $extHtml)) {
            // inject live reload script
            if (file_exists(__DIR__.'/livereload.js')) {
                $script = file_get_contents(__DIR__.'/livereload.js');
                $content = str_ireplace('</body>', "<script>$script</script>\n  </body>", $content);
                if (stristr($content, '</body>') === false) {
                    $content .= "\n$script";
                }
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
        switch ($ext) {
            case 'css':
                header('Content-Type: text/css');
                break;
            case 'js':
                header('Content-Type: application/javascript');
                break;
            case 'svg':
                header('Content-Type: image/svg+xml');
                break;
            default:
                header('Content-Type: '.$mimeType);
                break;
        }
        echo $content;

        return true;
    }

    // audio file headers
    if (in_array($mimeSubtype, $mimesAudio)) {
        header("Content-Type: $mimeType");
        header('Cache-Control: no-cache');
        header('Content-Transfer-Encoding: binary');
        header('Content-Length: '.filesize($filename));
        header('Accept-Ranges: bytes');
        echo file_get_contents($filename);

        return true;
    }

    return false;
}

// HTTP response: 404
http_response_code(404);
echo '404, page not found';
