<?php
/**
 * This file is part of the Cecil/Cecil package.
 *
 * Copyright (c) Arnaud Ligny <arnaud@ligny.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cecil\Generator;

use Cecil\Collection\Page\Page;
use Cecil\Converter\Converter;
use Exception;

/**
 * Class Generator\ExternalBody.
 */
class ExternalBody extends AbstractGenerator implements GeneratorInterface
{
    /**
     * {@inheritdoc}
     */
    public function generate(): void
    {
        $filteredPages = $this->pagesCollection->filter(function (Page $page) {
            return null !== $page->getVariable('external');
        });

        /** @var Page $page */
        foreach ($filteredPages as $page) {
            try {
                $pageContent = @file_get_contents($page->getVariable('external'), false);
                if ($pageContent === false) {
                    throw new Exception(sprintf('Cannot get contents from "%s"', $page->getVariable('external')));
                }
                $html = (new Converter())
                    ->convertBody($pageContent);
                $page->setBodyHtml($html);

                $this->generatedPages->add($page);
            } catch (\Exception $e) {
                $message = sprintf('%s: %s', $page->getId(), $e->getMessage());
                call_user_func_array($this->messageCallback, ['GENERATE_ERROR', $message, 1, count($filteredPages)]);
            }
        }
    }
}
