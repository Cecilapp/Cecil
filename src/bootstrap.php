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

use Composer\Autoload\ClassLoader;

function includeIfExists(string $file): ?ClassLoader
{
    return file_exists($file) ? include $file : null;
}

// includes then returns autoloader
switch (true) {
    case ($loader = includeIfExists(__DIR__ . '/../vendor/autoload.php')): // standalone
        break;
    case ($loader = includeIfExists(__DIR__ . '/../../../autoload.php')): // as a Composer dependency
        break;
    case ($loader = includeIfExists('vendor/autoload.php')): // as a Composer dependency, relative to CWD
        break;
    default:
        printf('You must set up the project dependencies using `composer install`%sSee https://getcomposer.org/download/ for instructions on installing Composer%s', PHP_EOL, PHP_EOL);
        exit(1);
}

return $loader;
