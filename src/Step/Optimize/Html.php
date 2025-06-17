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

namespace Cecil\Step\Optimize;

use Cecil\Util;
use voku\helper\HtmlMin;

/**
 * Html optimization step.
 *
 * This class extends the AbstractOptimize class and provides functionality
 * to optimize HTML files. It uses the voku\helper\HtmlMin library to
 * minify HTML files, reducing their size and improving load times.
 * It initializes with the type 'html' and processes files by minifying them.
 * It also provides methods to encode and decode content for caching purposes.
 */
class Html extends AbstractOptimize
{
    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return 'Optimizing HTML';
    }

    /**
     * {@inheritdoc}
     */
    public function init(array $options): void
    {
        $this->type = 'html';
        parent::init($options);
    }

    /**
     * {@inheritdoc}
     */
    public function setProcessor(): void
    {
        $this->processor = new HtmlMin();
    }

    /**
     * {@inheritdoc}
     */
    public function processFile(\Symfony\Component\Finder\SplFileInfo $file): string
    {
        $html = Util\File::fileGetContents($file->getPathname());

        return $this->processor->minify($html);
    }

    /**
     * {@inheritdoc}
     */
    public function encode(?string $content = null): ?string
    {
        return json_encode($content);
    }

    /**
     * {@inheritdoc}
     */
    public function decode(?string $content = null): ?string
    {
        return json_decode((string) $content);
    }
}
