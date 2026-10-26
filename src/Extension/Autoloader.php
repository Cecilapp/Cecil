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

namespace Cecil\Extension;

use Cecil\BuildContextInterface;
use Cecil\Util;
use Composer\Autoload\ClassLoader;

/**
 * Extensions autoloader.
 *
 * Loads extensions classes from the site, then from the themes (in their order):
 * - with the PSR-4 mappings of the `extensions.autoload` configuration (e.g.: `MySite\: extensions/`);
 * - otherwise from the `extensions` directory, the class path matching its namespace (e.g.: `extensions/MySite/Foo.php`).
 *
 * Third-party dependencies of extensions are loaded from `extensions/vendor/autoload.php` (Composer), if any.
 */
class Autoloader
{
    /**
     * Reserved namespace of Cecil classes.
     */
    public const string RESERVED_NAMESPACE = 'Cecil\\';

    /**
     * Required vendor autoload files.
     * @var array<string, true>
     */
    private array $vendors = [];

    private bool $registered = false;

    public function __construct(protected BuildContextInterface $builder, protected string $dir = 'extensions')
    {
    }

    /**
     * Registers the autoloader (once).
     */
    public function register(): void
    {
        if ($this->registered) {
            return;
        }
        spl_autoload_register([$this, 'loadClass']);
        $this->registered = true;
    }

    /**
     * Loads the file of a class, if found in the site or in a theme.
     */
    public function loadClass(string $class): void
    {
        foreach ($this->getRoots() as $origin => $root) {
            foreach ($this->getCandidates($class) as $relativePath) {
                $file = Util::joinFile($root, $relativePath);
                if (is_readable($file)) {
                    require $file;
                    $this->checkNamespace($class, $origin);

                    return;
                }
            }
        }
        // the class may be a third-party dependency of an extension
        if ($this->requireVendors()) {
            class_exists($class);
        }
    }

    /**
     * Requires the Composer autoload files of the site and themes extensions (`extensions/vendor/autoload.php`), not already required.
     * Their class loaders are registered after the Cecil ones: dependencies already provided by Cecil are not overridden.
     *
     * @return bool True if at least one file has been required
     */
    public function requireVendors(): bool
    {
        $required = false;
        foreach ($this->getRoots() as $root) {
            $file = Util::joinFile($root, $this->dir, 'vendor', 'autoload.php');
            if (isset($this->vendors[$file]) || !is_readable($file)) {
                continue;
            }
            $this->vendors[$file] = true;
            $loader = require $file;
            if ($loader instanceof ClassLoader) {
                $loader->unregister();
                $loader->register(false);
            }
            $this->builder->getLogger()->debug(\sprintf('Extensions dependencies loaded from "%s"', $file));
            $required = true;
        }

        return $required;
    }

    /**
     * Returns the root directories where to look for classes, indexed by origin ("site" or "theme:<name>").
     *
     * @return array<string, string>
     */
    protected function getRoots(): array
    {
        $config = $this->builder->getConfig();
        $roots = ['site' => $config->getSourceDir()];
        foreach ($config->getTheme() ?? [] as $theme) {
            $roots["theme:$theme"] = $config->getThemeDirPath($theme, '');
        }

        return $roots;
    }

    /**
     * Returns the possible paths of the class file, relative to a root directory:
     * from the `extensions.autoload` mappings (longest prefix first), then from the `extensions` directory.
     *
     * @return list<string>
     */
    protected function getCandidates(string $class): array
    {
        $candidates = [];
        $mappings = [];
        foreach ((array) $this->builder->getConfig()->get('extensions.autoload') as $prefix => $dir) {
            $prefix = trim((string) $prefix, '\\') . '\\';
            if (\is_string($dir) && $prefix !== '\\' && str_starts_with($class, $prefix)) {
                $mappings[$prefix] = $dir;
            }
        }
        uksort($mappings, fn (string $a, string $b) => \strlen($b) <=> \strlen($a));
        foreach ($mappings as $prefix => $dir) {
            $candidates[] = Util::joinPath($dir, str_replace('\\', '/', substr($class, \strlen($prefix))) . '.php');
        }
        $candidates[] = Util::joinPath($this->dir, str_replace('\\', '/', $class) . '.php');

        return $candidates;
    }

    /**
     * Reports a class loaded in the reserved Cecil namespace (deprecated):
     * as a warning for a site class, as a debug message for a theme class.
     */
    protected function checkNamespace(string $class, string $origin): void
    {
        if (!str_starts_with($class, self::RESERVED_NAMESPACE)) {
            return;
        }
        $message = \sprintf('Extension class "%s" (%s) uses the reserved "Cecil" namespace, which is deprecated: use your own namespace.', $class, $origin);
        if ($origin === 'site') {
            $this->builder->getLogger()->warning($message);

            return;
        }
        $this->builder->getLogger()->debug($message);
    }
}
