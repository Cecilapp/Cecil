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

namespace Cecil\Command;

use Cecil\Builder;
use Cecil\Collection\Page\Page;
use Cecil\Renderer\Page as PageRenderer;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * SEO doctor command.
 *
 * This command builds the site in dry-run mode and audits the rendered HTML
 * pages for a focused set of SEO checks inspired by editorial audit tools.
 */
class DoctorSeo extends AbstractCommand
{
    /** Default configuration thresholds */
    private const DEFAULT_CONFIG = [
        //'title.min' => 30,
        'title.min' => 8,
        //'title.max' => 60,
        'title.max' => 100,
        //'description.min' => 120,
        'description.min' => 8,
        'description.max' => 160,
        //'content.min_words' => 300,
        'content.min_words' => 8,
        'checks' => [
            'title' => true,
            'description' => true,
            'canonical' => true,
            'h1' => true,
            'og_tags' => true,
            'img_alt' => true,
            'content_length' => true,
            'lang_attribute' => true,
        ],
    ];

    private array $thresholds = [];
    private array $checks = [];
    private bool $includeVirtual = false;
    private string $format = 'text';

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('doctor:seo')
            ->setDescription('Audits rendered HTML pages for common SEO issues')
            ->setDefinition([
                new InputArgument('path', InputArgument::OPTIONAL, 'Use the given path as working directory'),
                new InputOption('config', 'c', InputOption::VALUE_REQUIRED, 'Set the path to an extra configuration file'),
                new InputOption('page', 'p', InputOption::VALUE_REQUIRED, 'Audit a single page relative to the pages directory'),
                new InputOption('format', null, InputOption::VALUE_REQUIRED, 'Output format: text (default) or json', 'text'),
                new InputOption('include-virtual', null, InputOption::VALUE_NONE, 'Include virtual pages (paginated, taxonomies) in audit'),
            ])
            ->setHelp(
                <<<'EOF'
The <info>%command.name%</> command builds the site in dry-run mode, then audits
the rendered HTML output for a focused set of SEO checks.

  <info>%command.full_name%</>
  <info>%command.full_name% path/to/the/working/directory</>

To audit a single page, run:

  <info>%command.full_name% --page=blog/post.md</>

To output results as JSON for CI integration:

  <info>%command.full_name% --format=json</>

To include virtual pages (paginated, taxonomy pages):

  <info>%command.full_name% --include-virtual</>

To inspect a site with an extra configuration file, run:

  <info>%command.full_name% --config=config.yml</>

Configure audit thresholds and checks in your configuration:

  doctor.seo:
    title.min: 30
    title.max: 60
    description.min: 120
    description.max: 160
    content.min_words: 300
    checks:
      title: true
      description: true
      canonical: true
      h1: true
      og_tags: true
      img_alt: true
      content_length: true
      lang_attribute: true
EOF
            )
        ;
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->format = (string) ($input->getOption('format') ?? 'text');
        $this->includeVirtual = (bool) $input->getOption('include-virtual');

        $builder = $this->getBuilder();
        $config = $builder->getConfig();

        // Load thresholds from configuration
        $this->loadConfiguration($config);

        $output->writeln('Building site in dry-run mode for SEO audit...');
        $builder->build([
            'dry-run' => true,
            'page' => (string) ($input->getOption('page') ?? ''),
            'render-subset' => '',
            'drafts' => false,
        ]);

        // Filter pages: only published and rendered, exclude virtual by default
        $pages = $builder->getPages()->filter(function (Page $page) {
            if ($page->getVariable('published') !== true) {
                return false;
            }
            if (!isset($page->getRendered()['html']['output'])) {
                return false;
            }
            // Exclude virtual pages (paginated, taxonomies) unless --include-virtual
            if (!$this->includeVirtual && $page->isVirtual()) {
                return false;
            }
            return true;
        });

        $findings = [];
        $counts = [
            'bad' => 0,
            'ok' => 0,
            'feedback' => 0,
        ];
        $healthyPages = 0;

        /** @var Page $page */
        foreach ($pages as $page) {
            $pageFindings = $this->auditPage($builder, $page);
            if (empty($pageFindings)) {
                $healthyPages++;

                continue;
            }

            foreach ($pageFindings as $finding) {
                $counts[$finding['level']]++;
                $findings[] = [
                    'page' => $this->getPageLabel($builder, $page),
                    'level' => $finding['level'],
                    'check' => $finding['check'],
                    'details' => $finding['details'],
                ];
            }
        }

        // Output results based on format
        if ($this->format === 'json') {
            $this->outputJson($output, $pages, $findings, $counts, $healthyPages);
        } else {
            $this->outputText($output, $findings, $counts, $healthyPages);
        }

        return Command::SUCCESS;
    }

    /**
     * Load audit configuration from Cecil config.
     */
    private function loadConfiguration(\Cecil\Config $config): void
    {
        $seoConfig = (array) $config->get('doctor.seo') ?? [];

        // Merge with defaults
        $this->thresholds = [
            'title_min' => $seoConfig['title.min'] ?? self::DEFAULT_CONFIG['title.min'],
            'title_max' => $seoConfig['title.max'] ?? self::DEFAULT_CONFIG['title.max'],
            'description_min' => $seoConfig['description.min'] ?? self::DEFAULT_CONFIG['description.min'],
            'description_max' => $seoConfig['description.max'] ?? self::DEFAULT_CONFIG['description.max'],
            'min_word_count' => $seoConfig['content.min_words'] ?? self::DEFAULT_CONFIG['content.min_words'],
        ];

        $this->checks = array_merge(
            self::DEFAULT_CONFIG['checks'],
            (array) ($seoConfig['checks'] ?? [])
        );
    }

    /**
     * Output results in JSON format.
     */
    private function outputJson(OutputInterface $output, \Cecil\Collection\Page\Collection $pages, array $findings, array $counts, int $healthyPages): void
    {
        $result = [
            'summary' => [
                'pages_audited' => \count($pages),
                'pages_without_findings' => $healthyPages,
                'bad_count' => $counts['bad'],
                'ok_count' => $counts['ok'],
                'feedback_count' => $counts['feedback'],
            ],
            'findings' => $findings,
        ];

        $output->writeln(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }

    /**
     * Output results in text format (tables).
     */
    private function outputText(OutputInterface $output, array $findings, array $counts, int $healthyPages): void
    {
        $pagesWithFindings = [];
        foreach ($findings as $finding) {
            $pagesWithFindings[$finding['page']] = true;
        }

        $summary = new Table($output);
        $summary
            ->setHeaderTitle('SEO audit summary')
            ->setHeaders(['Metric', 'Value'])
            ->setRows([
                ['Pages audited', (string) ($healthyPages + \count($pagesWithFindings))],
                ['Pages without findings', (string) $healthyPages],
                ['Bad', (string) $counts['bad']],
                ['OK', (string) $counts['ok']],
                ['Feedback', (string) $counts['feedback']],
            ])
        ;
        $summary->setStyle('box')->render();

        if (!empty($findings)) {
            $rows = [];
            foreach ($findings as $finding) {
                $rows[] = [
                    $finding['page'],
                    $this->formatLevel($finding['level']),
                    $finding['check'],
                    $finding['details'],
                ];
            }

            $table = new Table($output);
            $table
                ->setHeaderTitle('SEO findings')
                ->setHeaders(['Page', 'Level', 'Check', 'Details'])
                ->setRows($rows)
            ;
            $table->setStyle('box')->render();
        }

        if ($counts['bad'] > 0) {
            $this->io->error(\sprintf('SEO audit found %d bad issue(s).', $counts['bad']));

            return;
        }

        if ($counts['ok'] > 0 || $counts['feedback'] > 0) {
            $this->io->warning(\sprintf('SEO audit found %d improvement(s).', $counts['ok'] + $counts['feedback']));

            return;
        }

        $this->io->success('No SEO issues found.');
    }

    /**
     * @return array<int, array{level: string, check: string, details: string}>
     */
    private function auditPage(Builder $builder, Page $page): array
    {
        $html = (string) $page->getRendered()['html']['output'];
        $xpath = $this->createXPath($html);
        if ($xpath === null) {
            return [[
                'level' => 'bad',
                'check' => 'Rendered HTML',
                'details' => 'The page could not be parsed as HTML.',
            ]];
        }

        $findings = [];

        // Check: Title tag
        if ($this->checks['title']) {
            $title = $this->getFirstNodeText($xpath, '//title');
            if ($title === '') {
                $findings[] = $this->createFinding('bad', 'Title tag', 'Missing <title> element.');
            } else {
                $titleLength = mb_strlen($title);
                if ($titleLength < $this->thresholds['title_min'] || $titleLength > $this->thresholds['title_max']) {
                    $findings[] = $this->createFinding(
                        'ok',
                        'Title length',
                        \sprintf('Current length: %d characters. Recommended range: %d-%d.', $titleLength, $this->thresholds['title_min'], $this->thresholds['title_max'])
                    );
                }
            }
        }

        // Check: Meta description
        if ($this->checks['description']) {
            $description = $this->getFirstNodeText($xpath, "//meta[translate(@name, 'ABCDEFGHIJKLMNOPQRSTUVWXYZ', 'abcdefghijklmnopqrstuvwxyz') = 'description']/@content");
            if ($description === '') {
                $findings[] = $this->createFinding('bad', 'Meta description', 'Missing meta description.');
            } else {
                $descriptionLength = mb_strlen($description);
                if ($descriptionLength < $this->thresholds['description_min'] || $descriptionLength > $this->thresholds['description_max']) {
                    $findings[] = $this->createFinding(
                        'ok',
                        'Meta description length',
                        \sprintf('Current length: %d characters. Recommended range: %d-%d.', $descriptionLength, $this->thresholds['description_min'], $this->thresholds['description_max'])
                    );
                }
            }
        }

        // Check: Canonical URL
        if ($this->checks['canonical']) {
            $canonical = $this->getFirstNodeText($xpath, "//link[contains(concat(' ', translate(normalize-space(@rel), 'ABCDEFGHIJKLMNOPQRSTUVWXYZ', 'abcdefghijklmnopqrstuvwxyz'), ' '), ' canonical ')]/@href");
            if ($canonical === '') {
                $level = $builder->getConfig()->isEnabled('canonicalurl') ? 'bad' : 'feedback';
                $details = $builder->getConfig()->isEnabled('canonicalurl')
                    ? 'Missing canonical link while canonical URLs are enabled.'
                    : 'No canonical link found in the rendered page.';
                $findings[] = $this->createFinding($level, 'Canonical URL', $details);
            }
        }

        // Check: Lang attribute
        if ($this->checks['lang_attribute']) {
            $lang = $this->getFirstNodeText($xpath, '/html/@lang');
            if ($lang === '') {
                $findings[] = $this->createFinding('feedback', 'HTML lang attribute', 'Missing lang attribute on the <html> element.');
            }
        }

        // Check: H1 heading structure
        if ($this->checks['h1']) {
            $h1Count = $this->countNodes($xpath, '//h1');
            if ($h1Count === 0) {
                $findings[] = $this->createFinding('bad', 'Heading structure', 'Missing <h1> element.');
            }
            if ($h1Count > 1) {
                $findings[] = $this->createFinding('ok', 'Heading structure', \sprintf('Found %d <h1> elements.', $h1Count));
            }
        }

        // Check: Open Graph tags
        if ($this->checks['og_tags']) {
            if ($this->getFirstNodeText($xpath, "//meta[@property='og:title']/@content") === '') {
                $findings[] = $this->createFinding('feedback', 'Open Graph title', 'Missing og:title meta tag.');
            }
            if ($this->getFirstNodeText($xpath, "//meta[@property='og:description']/@content") === '') {
                $findings[] = $this->createFinding('feedback', 'Open Graph description', 'Missing og:description meta tag.');
            }
            if ($this->getFirstNodeText($xpath, "//meta[@property='og:image']/@content") === '') {
                $findings[] = $this->createFinding('feedback', 'Open Graph image', 'Missing og:image meta tag.');
            }
        }

        // Check: Image alt attributes
        if ($this->checks['img_alt']) {
            $missingAltImages = $this->countNodes($xpath, "//img[not(@alt) or normalize-space(@alt) = '']");
            if ($missingAltImages > 0) {
                $findings[] = $this->createFinding('ok', 'Images alt text', \sprintf('%d image(s) are missing an alt attribute.', $missingAltImages));
            }
        }

        // Check: Content length
        if ($this->checks['content_length']) {
            $wordCount = $this->countWords($this->getBodyText($xpath, $html));
            if ($wordCount < $this->thresholds['min_word_count']) {
                $findings[] = $this->createFinding('feedback', 'Content length', \sprintf('Estimated body length: %d words.', $wordCount));
            }
        }

        return $findings;
    }

    /**
     * @return array{level: string, check: string, details: string}
     */
    private function createFinding(string $level, string $check, string $details): array
    {
        return [
            'level' => $level,
            'check' => $check,
            'details' => $details,
        ];
    }

    private function getPageLabel(Builder $builder, Page $page): string
    {
        $path = (new PageRenderer($builder, $page))->getPath('html');
        if ($path === '') {
            return '/';
        }

        return '/' . ltrim($path, '/');
    }

    private function formatLevel(string $level): string
    {
        return match ($level) {
            'bad' => '<error>Bad</error>',
            'ok' => '<comment>OK</comment>',
            default => '<info>Feedback</info>',
        };
    }

    private function createXPath(string $html): ?\DOMXPath
    {
        if (trim($html) === '') {
            return null;
        }

        $useInternalErrors = libxml_use_internal_errors(true);
        $document = new \DOMDocument('1.0', 'UTF-8');
        $loaded = $document->loadHTML('<?xml encoding="UTF-8">' . $html, LIBXML_NOERROR | LIBXML_NOWARNING | LIBXML_NONET);
        libxml_clear_errors();
        libxml_use_internal_errors($useInternalErrors);
        if ($loaded === false) {
            return null;
        }

        return new \DOMXPath($document);
    }

    private function getFirstNodeText(\DOMXPath $xpath, string $query): string
    {
        $nodes = $xpath->query($query);
        if ($nodes === false || $nodes->length === 0) {
            return '';
        }

        return $this->normalizeText((string) $nodes->item(0)?->textContent);
    }

    private function countNodes(\DOMXPath $xpath, string $query): int
    {
        $nodes = $xpath->query($query);
        if ($nodes === false) {
            return 0;
        }

        return $nodes->length;
    }

    private function getBodyText(\DOMXPath $xpath, string $html): string
    {
        $body = $this->getFirstNodeText($xpath, '//body');
        if ($body !== '') {
            return $body;
        }

        return $this->normalizeText((string) strip_tags($html));
    }

    private function countWords(string $text): int
    {
        if ($text === '') {
            return 0;
        }

        preg_match_all("/[\\p{L}\\p{N}']+/u", $text, $matches);

        return \count($matches[0]);
    }

    private function normalizeText(string $text): string
    {
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace('/\s+/u', ' ', $text);

        return trim((string) $text);
    }
}
