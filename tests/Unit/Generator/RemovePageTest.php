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

namespace Cecil\Test\Unit\Generator;

use Cecil\Builder;
use Cecil\Generator\AbstractGenerator;
use Cecil\Util;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Component\Filesystem\Filesystem;

class RemovePageTest extends TestCase
{
    public function testGeneratorCanRemovePages(): void
    {
        $filesystem = new Filesystem();
        $sourceDir = Util::joinFile(sys_get_temp_dir(), 'cecil-remove-page-' . uniqid('', true));
        $filesystem->dumpFile(Util::joinFile($sourceDir, 'pages', 'keep.md'), "---\ntitle: Keep\n---\n");
        $filesystem->dumpFile(Util::joinFile($sourceDir, 'pages', 'remove.md'), "---\ntitle: Remove\n---\n");

        try {
            $builder = (new Builder([
                'baseurl' => 'https://example.com/',
                'cache'   => false,
                'pages'   => ['generators' => ['Remover' => ['class' => RemoverGenerator::class, 'priority' => 5]]],
            ], new NullLogger()))->setSourceDir($sourceDir);
            $builder->build(['dry-run' => true]);

            self::assertTrue($builder->getPages()->has('keep'));
            self::assertFalse($builder->getPages()->has('remove'));
        } finally {
            $filesystem->remove($sourceDir);
        }
    }
}

/**
 * Removes the "remove" page, and an unknown page (ignored).
 */
class RemoverGenerator extends AbstractGenerator
{
    public function generate(): void
    {
        $this->removePage('remove');
        $this->removePage('unknown');
    }
}
