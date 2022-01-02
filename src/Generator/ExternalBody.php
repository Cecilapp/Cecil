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
use Cecil\Util;
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
        $filteredPages = $this->builder->getPages()->filter(function (Page $page) {
            return null !== $page->getVariable('external');
        });

        /** @var Page $page */
        foreach ($filteredPages as $page) {
            try {
                $pageContent = Util\File::fileGetContents($page->getVariable('external'));
                if ($pageContent === false) {
                    throw new Exception(sprintf('Cannot get contents from "%s".', $page->getVariable('external')));
                }
                $html = (new Converter($this->builder))->convertBody($pageContent);
                $page->setBodyHtml($html);

                $this->generatedPages->add($page);
            } catch (\Exception $e) {
                $message = sprintf('Error in page "%s": %s', $page->getId(), $e->getMessage());
                $this->builder->getLogger()->error($message);
            }
        }
    }
}
