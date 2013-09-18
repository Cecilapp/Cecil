<?php

use Zend\Console;
use Zend\Loader\StandardAutoloader;
use Michelf\Markdown;

// Composer autoloading
if (file_exists('vendor/autoload.php')) {
    $loader = include 'vendor/autoload.php';
}
// Includes ZF2 components
if (is_dir('vendor/zendframework')) {
    $zf2Path = 'vendor/zendframework';
    if (isset($loader)) {
        $loader->add('Zend', $zf2Path);
    }
}
if (!class_exists('Zend\Loader\AutoloaderFactory')) {
    echo 'Unable to load ZF2 components. Run `php composer.phar install`.';
    exit(2);
}

$pwd = getcwd();
$websiteDir = null;

// Defines rules
$rules = array(
    'help|h'       => 'Get PHPoole usage message',
    'init|i-s'     => 'Build a new website in <website>',
    'generate|g-s' => 'Generate static files of <website>',
    'serve|s-s'    => 'Start Built-in web server with <website> document root',
    'deploy|d-s'   => 'Deploy static <website>',
    'list|l=s'     => 'List <pages> or <posts>'
);

// Get and parse console options
try {
    $opts = new Console\Getopt($rules);
    $opts->parse();
} catch (Console\Exception\RuntimeException $e) {
    echo $e->getUsageMessage();
    exit(2);
}

// help option
if ($opts->getOption('help')) {
    echo $opts->getUsageMessage();
    exit(0);
}

// init option
if ($opts->getOption('init')) {
    if (isset($opts->i)) {
        if (!is_dir($opts->i)) {
            echo 'Invalid directory provided' . PHP_EOL
                . PHP_EOL;
            echo $opts->getUsageMessage();
            exit(2);
        }
        $websiteDir = $opts->i;
        $websiteDir = str_replace('\\', '/', realpath($websiteDir));
    }
    echo 'Initializing new website' . (!is_null($websiteDir) ? " in $websiteDir" : '') . PHP_EOL
        . PHP_EOL;
    Init((!is_null($websiteDir) ? $websiteDir : $pwd));
    echo "[OK] create .phpoole" . PHP_EOL;
    MakeConfigFile((!is_null($websiteDir) ? $websiteDir : $pwd) . '/.phpoole/config.ini');
    echo '[OK] create .phpoole/config.ini' . PHP_EOL;
    mkdir((!is_null($websiteDir) ? $websiteDir : $pwd) . '/.phpoole/layouts');
    echo '[OK] create .phpoole/layouts' . PHP_EOL;
    MakeLayoutBaseFile((!is_null($websiteDir) ? $websiteDir : $pwd) . '/.phpoole/layouts/base.html');
    echo '[OK] create .phpoole/layouts/base.html' . PHP_EOL;
    MakeAssetsDir((!is_null($websiteDir) ? $websiteDir : $pwd) . '/.phpoole');
    echo '[OK] create .phpoole/assets/...' . PHP_EOL;
    MakeContentDir((!is_null($websiteDir) ? $websiteDir : $pwd) . '/.phpoole');
    echo '[OK] create .phpoole/content/...' . PHP_EOL;
    MakeIndexFile((!is_null($websiteDir) ? $websiteDir : $pwd) . '/.phpoole/content/pages/index.md');
    echo '[OK] create .phpoole/content/pages/index.md' . PHP_EOL;
    exit(0);
}

// generate option
if ($opts->getOption('generate')) {
    if (isset($opts->g)) {
        if (!is_dir($opts->g)) {
            echo 'Invalid directory provided' . PHP_EOL
                . PHP_EOL;
            echo $opts->getUsageMessage();
            exit(2);
        }
        $websiteDir = $opts->g;
        $websiteDir = str_replace('\\', '/', realpath($websiteDir));
    }
    echo 'Generating website' . (!is_null($websiteDir) ? " in $websiteDir" : '') . PHP_EOL
        . PHP_EOL;
    $twigLoader = new Twig_Loader_Filesystem((!is_null($websiteDir) ? $websiteDir : $pwd) . '/.phpoole/layouts');
    $twig = new Twig_Environment($twigLoader, array('autoescape' => false));
    $iterator = new MardownFileFilter((!is_null($websiteDir) ? $websiteDir : $pwd) . '/.phpoole/content/pages');
    foreach ($iterator as $filePage) {
        $page = parseContent(
            file_get_contents($filePage->getPathname()),
            $filePage->getFilename()
        );
        $info = $page['info'];
        if (
            isset($info['layout'])
            && is_file(
                (!is_null($websiteDir) ? $websiteDir : $pwd) . '/.phpoole/layouts' . '/' . $info['layout'] . '.html'
            )
        ) {
            $layout = $info['layout'] . '.html';
        }
        else {
            $layout = 'base.html';
        }
        $rendered = $twig->render($layout, array(
            'content' => $page['content']
        ));
        if (is_file((!is_null($websiteDir) ? $websiteDir : $pwd) . '/' . $filePage->getBasename('.md') . '.html')) {
            unlink((!is_null($websiteDir) ? $websiteDir : $pwd) . '/' . $filePage->getBasename('.md') . '.html');
            echo '[OK] delete ' . $filePage->getBasename('.md') . '.html' . PHP_EOL;
        }
        file_put_contents(
            (!is_null($websiteDir) ? $websiteDir : $pwd) . '/' . $filePage->getBasename('.md') . '.html', $rendered
        );
        echo '[OK] write ' . $filePage->getBasename('.md') . '.html' . PHP_EOL;
    }
    exit(0);
}

// Serve option
if ($opts->getOption('serve')) {
    if (isset($opts->s)) {
        if (!is_dir($opts->s)) {
            echo 'Invalid directory provided' . PHP_EOL
                . PHP_EOL;
            echo $opts->getUsageMessage();
            exit(2);
        }
        $websiteDir = $opts->s;
        $websiteDir = str_replace('\\', '/', realpath($websiteDir));
    }
    printf(
        'Start server http://%s:%d' . PHP_EOL,
        'localhost',
        '8000'
    );
    $command = sprintf(
        //'php -S %s:%d -t %s >/dev/null 2>&1 & echo $!',
        'START /B php -S %s:%d -t %s > nul',
        'localhost',
        '8000',
        $websiteDir
    );
    $output = array(); 
    exec($command);
    echo 'Server stopped' . PHP_EOL;
    exit(0);
}

// Deploy option
if ($opts->getOption('deploy')) {
    echo 'Deploy to GitHub pages?' . PHP_EOL
        . PHP_EOL;
    exit(0);
}

// List option
if ($opts->getOption('list')) {
    //print_r($opts->l) . PHP_EOL;
    //print_r($opts->getRemainingArgs([0])) . PHP_EOL;
    $arg2 = $opts->getRemainingArgs();
    if (isset($arg2[0])) {
        if (!is_dir($arg2[0])) {
            echo 'Invalid directory provided' . PHP_EOL
                . PHP_EOL;
            echo $opts->getUsageMessage();
            exit(2);
        }
        $websiteDir = $arg2[0];
        $websiteDir = str_replace('\\', '/', realpath($websiteDir));
    }
    $type = $opts->l;
    if ($type == 'pages') {
        echo 'List pages' . (!is_null($websiteDir) ? " in $websiteDir" : '') . PHP_EOL
            . PHP_EOL;
        if (!is_dir($websiteDir . '/.phpoole/content/pages')) {
            echo 'Invalid pages directory' . PHP_EOL
                . PHP_EOL;
            echo $opts->getUsageMessage();
            exit(2);
        }
        $fs = new FilesystemIterator($websiteDir . '/.phpoole/content/pages');
        foreach($fs as $file) {
            if ($file->isFile()) {
                echo '- ' . $file->getFilename() . PHP_EOL;
            }
        }
        exit(0);
    }
    else if ($type == 'posts') {
        echo 'List posts' . (!is_null($websiteDir) ? " in $websiteDir" : '') . PHP_EOL;
    }
    else {
        exit(2);
    }
}

// Displays usage message by default
echo $opts->getUsageMessage();
exit(2);


/**
 * PHPoole helpers
 */

function Init($path) {
    if (is_dir($path . '/.phpoole')) {
        RecursiveRmdir($path . '/.phpoole');
    }
    mkdir($path . '/.phpoole');
}

function MakeConfigFile($filePath) {
    $content = <<<EOL
[site]
title        = "PHPoole"
baseline     = "PHPoole is a simple static website/weblog generator written in PHP."
description  = "PHPoole is (will be!) a simple static website/weblog generator written in PHP. It parses your content written with Markdown, merge it with layouts and generates static HTML files."
base_url     = "http://localhost/PHPoole"
language     = "en_US"
[author]
name  = "Arnaud Ligny"
email = "arnaud+phpoole@ligny.org"
home  = "http://narno.org"
EOL;
    if (is_file($filePath)) {
        unlink($filePath);
    }
    file_put_contents($filePath, $content);
}

function MakeLayoutBaseFile($filePath) {
    $content = <<<EOL
<!DOCTYPE html>
<!--[if IE 8]><html class="no-js lt-ie9" lang="en"><![endif]-->
<!--[if gt IE 8]><!--><html class="no-js" lang="en"><!--<![endif]-->
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width">
  <title>{{ site.title }}</title>
</head>
<body>
  {{ content }}
</body>
</html>
EOL;
    if (is_file($filePath)) {
        unlink($filePath);
    }
    file_put_contents($filePath, $content);
}

function MakeAssetsDir($path) {
    mkdir($path . '/assets');
    mkdir($path . '/assets/css');
    mkdir($path . '/assets/img');
    mkdir($path . '/assets/js');
}

function MakeContentDir($path) {
    mkdir($path . '/content');
    mkdir($path . '/content/posts');
    mkdir($path . '/content/pages');
}

function MakeIndexFile($filePath) {
    $content = <<<EOL
PHPoole
EOL;
    if (is_file($filePath)) {
        unlink($filePath);
    }
    file_put_contents($filePath, $content);
}

function parseContent($content, $filename) {
    preg_match('/^<!--(.+)-->(.+)/s', $content, $matches);
    if (!$matches) {
        printf('Could not parse front matter in %s', $filename) . PHP_EOL;
        exit(2);
    }
    list($matchesAll, $rawInfo, $rawBody) = $matches;
    $info = parse_ini_string(preg_replace('/[[:cntrl:]]/', '', $rawInfo));
    $html = Markdown::defaultTransform($content);
    return [
        'info'    => $info,
        'content' => $html
    ];
}


/**
 * Utils
 */

/**
 * Recursively remove a directory
 *
 * @param string $dirname
 * @param boolean $followSymlinks
 * @return boolean
 */
function RecursiveRmdir($dirname, $followSymlinks=false)
{
    if (is_dir($dirname) && !is_link($dirname)) {
        if (!is_writable($dirname)) {
            throw new Exception(sprintf('%s is not writable!', $dirname));
        }
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dirname),
            RecursiveIteratorIterator::CHILD_FIRST
        );
        while ($iterator->valid()) {
            if (!$iterator->isDot()) {
                if (!$iterator->isWritable()) {
                    throw new Exception(sprintf(
                        '%s is not writable!',
                        $iterator->getPathName()
                    ));
                }
                if ($iterator->isLink() && $followLinks === false) {
                    $iterator->next();
                }
                if ($iterator->isFile()) {
                    unlink($iterator->getPathName());
                }
                elseif ($iterator->isDir()) {
                    rmdir($iterator->getPathName());
                }
            }
            $iterator->next();
        }
        unset($iterator);
 
        return rmdir($dirname);
    }
    else {
        throw new Exception(sprintf('%s does not exist!', $dirname));
    }
}

class MardownFileFilter extends FilterIterator
{
    public function __construct($dirOrIterator = '.')
    {
        if (is_string($dirOrIterator)) {
            if (!is_dir($dirOrIterator)) {
                throw new Exception\InvalidArgumentException('Expected a valid directory name');
            }
            $dirOrIterator = new RecursiveDirectoryIterator($dirOrIterator, RecursiveDirectoryIterator::FOLLOW_SYMLINKS);
        }
        elseif (!$dirOrIterator instanceof DirectoryIterator) {
            throw new Exception\InvalidArgumentException('Expected a DirectoryIterator');
        }
        if ($dirOrIterator instanceof RecursiveIterator) {
            $dirOrIterator = new RecursiveIteratorIterator($dirOrIterator);
        }
        parent::__construct($dirOrIterator);
    }

    public function accept()
    {
        $file = $this->getInnerIterator()->current();

        if (!$file instanceof SplFileInfo) {
            return false;
        }
        if (!$file->isFile()) {
            return false;
        }
        if ($file->getExtension() == 'md') {
            return true;
        }
    }
}