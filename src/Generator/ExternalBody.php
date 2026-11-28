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

namespace Cecil\Generator;

use Cecil\Collection\Page\Page;
use Cecil\Exception\RuntimeException;
use Cecil\Util;
use Symfony\Component\Filesystem\Path;

/**
 * ExternalBody generator class.
 *
 * This class is responsible for generating the body of pages
 * by fetching content from external sources specified in the 'external' variable of each page.
 * It reads the content from the specified URL, converts it to HTML using the Converter,
 * and sets the resulting HTML as the body of the page.
 * If an error occurs while fetching the content, it logs the error message.
 */
class ExternalBody extends AbstractGenerator implements GeneratorInterface
{
    /**
     * {@inheritdoc}
     */
    #[\Override]
    public function generate(): void
    {
        $filteredPages = $this->builder->getPages()->filter(function (Page $page) {
            return $page->getVariable('external') !== null;
        });

        /** @var Page $page */
        foreach ($filteredPages as $page) {
            try {
                $pageContent = Util\File::fileGetContents($this->resolveSource((string) $page->getVariable('external')));
                if ($pageContent === false) {
                    throw new RuntimeException(\sprintf('Unable to get external contents from "%s".', $page->getVariable('external')));
                }
                $language = $page->getVariable('language');
                if ($language === $this->builder->getConfig()->getLanguageDefault()) {
                    $language = null;
                }
                $html = $this->getBuilder()->getConverter()->convertBody($pageContent, $language);
                $page->setBodyHtml($html);

                $this->generatedPages->add($page);
            } catch (\Exception $e) {
                $message = \sprintf('Error in "%s": %s', $page->getFilePath(), $e->getMessage());
                $this->builder->getLogger()->error($message);
            }
        }
    }

    /**
     * Returns the source to read, after checking it is allowed.
     *
     * - a remote source must be an HTTP(S) URL (other stream wrappers are rejected);
     * - a local path is resolved against the site directory and must stay inside its parent directory.
     *
     * @throws RuntimeException
     */
    private function resolveSource(string $external): string
    {
        // URL: scheme of 2+ characters (a Windows drive letter, like "C:", is a local path)
        if (preg_match('#^([a-z][a-z0-9+.\-]+):#i', $external, $matches)) {
            if (!\in_array(strtolower($matches[1]), ['http', 'https'], true)) {
                throw new RuntimeException(\sprintf('The scheme "%s" is not allowed in "external" (only "http" and "https").', $matches[1]));
            }

            return $external;
        }

        // local path: resolved against the site directory, confined to its parent directory
        $siteDir = Path::canonicalize($this->builder->getConfig()->getSourceDir());
        $allowedDir = Path::getDirectory($siteDir);
        // resolves symbolic links to check the real location of the file
        if (false === $realPath = realpath(Path::makeAbsolute($external, $siteDir))) {
            throw new RuntimeException(\sprintf('The file "%s" does not exist.', $external));
        }
        $realAllowedDir = realpath($allowedDir) ?: $allowedDir;
        if (!Path::isBasePath($realAllowedDir, $realPath)) {
            throw new RuntimeException(\sprintf('The file "%s" is outside the allowed directory ("%s").', $external, $allowedDir));
        }
        if (!is_file($realPath)) {
            throw new RuntimeException(\sprintf('"%s" is not a file.', $external));
        }

        return $realPath;
    }
}
