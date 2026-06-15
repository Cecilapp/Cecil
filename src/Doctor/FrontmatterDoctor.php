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

namespace Cecil\Doctor;

use Cecil\Builder;
use Cecil\Collection\Page\Page;
use Cecil\Converter\Converter;
use Cecil\Exception\RuntimeException;
use Cecil\Step\Pages\Load as PagesLoadStep;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

/**
 * Front matter diagnosis domain service.
 */
class FrontmatterDoctor
{
    /**
     * @param array{page?: string} $options
     *
     * @return array{
     *   summary: array{files_scanned: int, files_with_frontmatter: int, valid_frontmatters: int, invalid_frontmatters: int, files_without_frontmatter: int},
        *   findings: array<int, array{file: string, file_absolute: string, line: int|null, status: string, details: string}>
     * }
     */
    public function diagnose(Builder $builder, array $options = []): array
    {
        $page = (string) ($options['page'] ?? '');
        $step = new PagesLoadStep($builder);
        $step->init(['page' => $page]);
        if ($step->canProcess()) {
            $step->process();
        }

        $files = $builder->getPagesFiles();
        if (!$files instanceof Finder) {
            return [
                'summary' => [
                    'files_scanned' => 0,
                    'files_with_frontmatter' => 0,
                    'valid_frontmatters' => 0,
                    'invalid_frontmatters' => 0,
                    'files_without_frontmatter' => 0,
                ],
                'findings' => [],
            ];
        }

        $format = (string) $builder->getConfig()->get('pages.frontmatter');
        $converter = new Converter($builder);

        $filesScanned = 0;
        $filesWithFrontmatter = 0;
        $validFrontmatters = 0;
        $invalidFrontmatters = 0;
        $filesWithoutFrontmatter = 0;
        $findings = [];

        /** @var SplFileInfo $file */
        foreach ($files as $file) {
            $filesScanned++;
            $fileRelativePath = $file->getRelativePathname();
            $fileAbsolutePath = $file->getRealPath() ?: $file->getPathname();

            try {
                $parsedPage = (new Page($file))->parse();
            } catch (RuntimeException $e) {
                $invalidFrontmatters++;
                $findings[] = [
                    'file' => $fileRelativePath,
                    'file_absolute' => $fileAbsolutePath,
                    'line' => $e->getLine() > 0 ? $e->getLine() : null,
                    'status' => 'error',
                    'details' => $e->getMessage(),
                ];

                continue;
            }

            $frontmatter = $parsedPage->getFrontmatter();
            if ($frontmatter === null || trim($frontmatter) === '') {
                $filesWithoutFrontmatter++;

                continue;
            }

            $filesWithFrontmatter++;

            try {
                $converter->convertFrontmatter($frontmatter, $format);
                $validFrontmatters++;
            } catch (RuntimeException $e) {
                $invalidFrontmatters++;

                $findings[] = [
                    'file' => $fileRelativePath,
                    'file_absolute' => $fileAbsolutePath,
                    'line' => $e->getLine() > 0 ? $e->getLine() : null,
                    'status' => 'error',
                    'details' => $e->getMessage(),
                ];
            }
        }

        return [
            'summary' => [
                'files_scanned' => $filesScanned,
                'files_with_frontmatter' => $filesWithFrontmatter,
                'valid_frontmatters' => $validFrontmatters,
                'invalid_frontmatters' => $invalidFrontmatters,
                'files_without_frontmatter' => $filesWithoutFrontmatter,
            ],
            'findings' => $findings,
        ];
    }
}
