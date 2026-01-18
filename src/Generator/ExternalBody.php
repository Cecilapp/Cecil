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

use Cecil\Builder;
use Cecil\Collection\Page\Page;
use Cecil\Converter\Converter;
use Cecil\Exception\RuntimeException;
use Cecil\Util;

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
    /** @var Converter */
    protected $converter;

    public function __construct(Builder $builder, Converter $converter)
    {
        parent::__construct($builder);
        $this->converter = $converter;
    }

    /**
     * {@inheritdoc}
     */
    public function generate(): void
    {
        $filteredPages = $this->builder->getPages()->filter(function (Page $page) {
            return $page->getVariable('external') !== null;
        });

        /** @var Page $page */
        foreach ($filteredPages as $page) {
            try {
                $pageContent = Util\File::fileGetContents($page->getVariable('external'));
                if ($pageContent === false) {
                    throw new RuntimeException(\sprintf('Unable to get external contents from "%s".', $page->getVariable('external')));
                }
                $html = $this->converter->convertBody($pageContent);
                $page->setBodyHtml($html);

                $this->generatedPages->add($page);
            } catch (\Exception $e) {
                $message = \sprintf('Error in "%s": %s', $page->getFilePath(), $e->getMessage());
                $this->builder->getLogger()->error($message);
            }
        }
    }
}
