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

namespace Cecil;

use Cecil\Collection\Page\Collection as PagesCollection;
use Cecil\Config;
use Psr\Log\LoggerInterface;
use Symfony\Component\Finder\Finder;

/**
 * Exposes the minimal surface of Builder needed by Steps and Generators.
 *
 * Making Builder implement this interface and typing dependencies against it
 * reduces coupling and simplifies testing.
 */
interface BuildContextInterface
{
    /**
     * Returns configuration.
     */
    public function getConfig(): Config;

    /**
     * Returns the logger instance.
     */
    public function getLogger(): LoggerInterface;

    /**
     * Returns debug mode state.
     */
    public function isDebug(): bool;

    /**
     * Returns build options.
     */
    public function getBuildOptions(): array;

    /**
     * Set collected pages files.
     */
    public function setPagesFiles(Finder $content): void;

    /**
     * Returns pages files.
     */
    public function getPagesFiles(): ?Finder;

    /**
     * Set collected data.
     */
    public function setData(array $data): void;

    /**
     * Returns data collection.
     */
    public function getData(?string $language = null): array;

    /**
     * Set collected static files.
     */
    public function setStatic(array $static): void;

    /**
     * Set/update Pages collection.
     */
    public function setPages(PagesCollection $pages): void;

    /**
     * Returns pages collection.
     */
    public function getPages(): ?PagesCollection;

    /**
     * Returns list of assets path.
     */
    public function getAssetsList(): array;

    /**
     * Set menus collection.
     */
    public function setMenus(array $menus): void;

    /**
     * Set taxonomies collection.
     */
    public function setTaxonomies(array $taxonomies): void;

    /**
     * Returns taxonomies collection, for a language.
     */
    public function getTaxonomies(string $language): ?\Cecil\Collection\Taxonomy\Collection;

    /**
     * Set renderer object.
     */
    public function setRenderer(\Cecil\Renderer\Twig $renderer): void;

    /**
     * Returns Renderer object.
     */
    public function getRenderer(): \Cecil\Renderer\Twig;

    /**
     * Records a layout cache access during rendering.
     */
    public function recordLayoutCacheAccess(bool $hit): void;
}
