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

use Cecil\Exception\RuntimeException;
use Cecil\Util;
use Dompdf\Dompdf;
use Dompdf\Options;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Filesystem\Path;
use Symfony\Component\Finder\Finder;

/**
 * ExportPdf command.
 *
 * This command exports the website as a single PDF file: the HTML files of the output directory
 * are merged - one document part per HTML file - then converted to PDF with Dompdf.
 */
class ExportPdf extends AbstractCommand
{
    /** Elements removed from the exported document. */
    private const REMOVED_ELEMENTS = ['script', 'noscript', 'iframe', 'object', 'embed', 'form', 'template'];

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('export:pdf')
            ->setDescription('Exports the website as a PDF file')
            ->setDefinition([
                new InputArgument('path', InputArgument::OPTIONAL, 'Use the given path as working directory'),
                new InputOption('file', 'f', InputOption::VALUE_REQUIRED, 'Set the path of the exported PDF file'),
                new InputOption('paper', null, InputOption::VALUE_REQUIRED, 'Set the paper size (e.g.: "A4", "letter")'),
                new InputOption('orientation', null, InputOption::VALUE_REQUIRED, 'Set the paper orientation ("portrait" or "landscape")'),
                new InputOption('styles', null, InputOption::VALUE_NONE, 'Include the stylesheets of the pages'),
                new InputOption('drafts', 'd', InputOption::VALUE_NONE, 'Include drafts'),
                new InputOption('no-build', null, InputOption::VALUE_NONE, 'Export the current content of the output directory, without building the website first'),
                new InputOption('config', 'c', InputOption::VALUE_REQUIRED, 'Set the path to extra config files (comma-separated)'),
            ])
            ->setHelp(
                <<<'EOF'
The <info>%command.name%</> command builds the website then exports it as a single PDF file.

  <info>%command.full_name%</>
  <info>%command.full_name% path/to/the/working/directory</>

To export the website in a specific file, run:

  <info>%command.full_name% --file=website.pdf</>

To export the <comment>current content</comment> of the output directory, without building the website first, run:

  <info>%command.full_name% --no-build</>

The <comment>stylesheets</comment> of the pages are ignored by default, because the CSS support of the PDF renderer is limited.
To include them, run:

  <info>%command.full_name% --styles</>
EOF
            );
    }

    /**
     * {@inheritdoc}
     *
     * @throws RuntimeException
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $builder = $this->getBuilder();
        $config = $builder->getConfig();

        // builds the website first, unless --no-build
        if (!$input->getOption('no-build')) {
            $this->io->title('Build website');
            $builder->build(['drafts' => (bool) $input->getOption('drafts')]);
            $output->writeln('<info>Build done.</info>');
        }

        $this->io->title('Export website as PDF');

        $outputPath = $config->getOutputPath();
        if (!is_dir($outputPath)) {
            throw new RuntimeException(\sprintf('The output directory "%s" doesn\'t exists: build the website first.', $outputPath));
        }

        $file = (string) ($input->getOption('file') ?: $config->get('export.pdf.file'));
        if (!Path::isAbsolute($file)) {
            $file = Util::joinFile($this->getPath(), $file);
        }
        $paper = (string) ($input->getOption('paper') ?: $config->get('export.pdf.paper'));
        $orientation = (string) ($input->getOption('orientation') ?: $config->get('export.pdf.orientation'));
        $styles = (bool) $input->getOption('styles') || $config->isEnabled('export.pdf.styles');

        $files = $this->collectHtmlFiles($outputPath);
        if (empty($files)) {
            throw new RuntimeException(\sprintf('There is no HTML file to export in "%s".', $outputPath));
        }
        $output->writeln(\sprintf('<comment>Pages: %s</comment>', \count($files)), OutputInterface::VERBOSITY_VERBOSE);

        Util\File::getFS()->dumpFile($file, $this->render($this->buildDocument($files, $outputPath, $styles), $outputPath, $paper, $orientation));

        $output->writeln(\sprintf('<info>Website exported to "%s".</info>', $file));

        return Command::SUCCESS;
    }

    /**
     * Returns the list of the HTML files of the output directory, the homepage first.
     *
     * @return array<string> Absolute paths.
     */
    private function collectHtmlFiles(string $outputPath): array
    {
        $files = [];
        $finder = (new Finder())->files()->in($outputPath)->name('/\.html?$/')->sortByName(true);
        foreach ($finder as $file) {
            $files[] = (string) $file->getRealPath();
        }
        $homepage = Util::joinFile($outputPath, 'index.html');
        if (\in_array($homepage, $files, true)) {
            $files = array_merge([$homepage], array_diff($files, [$homepage]));
        }

        return array_values($files);
    }

    /**
     * Merges the HTML files into a single HTML document, ready to be converted.
     *
     * @param array<string> $files
     */
    private function buildDocument(array $files, string $outputPath, bool $styles): string
    {
        $stylesheets = '';
        $body = '';
        foreach ($files as $file) {
            $document = $this->loadHtmlFile($file);
            if ($document === null) {
                continue;
            }
            if ($styles && empty($stylesheets)) {
                $stylesheets = $this->extractStylesheets($document, $file, $outputPath);
            }
            $body .= \sprintf("<div class=\"cecil-page\">\n%s\n</div>\n", $this->extractBody($document, $file, $outputPath));
        }

        return <<<HTML
            <!DOCTYPE html>
            <html>
            <head>
            <meta charset="UTF-8">
            $stylesheets
            <style>
            @page { margin: 2cm 1.5cm; }
            body { font-family: DejaVu Sans, sans-serif; font-size: 11pt; }
            img { max-width: 100%; }
            .cecil-page { page-break-after: always; }
            </style>
            </head>
            <body>
            $body
            </body>
            </html>
            HTML;
    }

    /**
     * Loads an HTML file as a DOM document.
     */
    private function loadHtmlFile(string $file): ?\DOMDocument
    {
        $content = file_get_contents($file);
        if ($content === false || trim($content) === '') {
            return null;
        }
        $document = new \DOMDocument();
        $internalErrors = libxml_use_internal_errors(true);
        // the XML declaration preserves UTF-8 characters and LIBXML_NONET disables network access
        $loaded = $document->loadHTML('<?xml encoding="UTF-8">' . $content, LIBXML_NONET | LIBXML_NOERROR | LIBXML_NOWARNING);
        libxml_clear_errors();
        libxml_use_internal_errors($internalErrors);

        return $loaded ? $document : null;
    }

    /**
     * Extracts the content of the `body` element, with local resources path resolved.
     */
    private function extractBody(\DOMDocument $document, string $file, string $outputPath): string
    {
        foreach (self::REMOVED_ELEMENTS as $name) {
            foreach (iterator_to_array($document->getElementsByTagName($name)) as $node) {
                $node->parentNode?->removeChild($node);
            }
        }
        $xpath = new \DOMXPath($document);
        /** @var \DOMElement $element */
        foreach ($xpath->query('//img[@src]') ?: [] as $element) {
            $path = $this->resolvePath((string) $element->getAttribute('src'), $file, $outputPath);
            if ($path === null) {
                $element->parentNode?->removeChild($element);
                continue;
            }
            $element->setAttribute('src', $path);
        }
        $body = $document->getElementsByTagName('body')->item(0);
        if ($body === null) {
            return '';
        }
        $html = '';
        foreach ($body->childNodes as $child) {
            $html .= (string) $document->saveHTML($child);
        }

        return $html;
    }

    /**
     * Extracts the local stylesheets of a document as inline `style` elements.
     */
    private function extractStylesheets(\DOMDocument $document, string $file, string $outputPath): string
    {
        $styles = '';
        $xpath = new \DOMXPath($document);
        /** @var \DOMElement $element */
        foreach ($xpath->query('//link[@rel="stylesheet"][@href]') ?: [] as $element) {
            $path = $this->resolvePath((string) $element->getAttribute('href'), $file, $outputPath);
            if ($path === null) {
                continue;
            }
            $content = file_get_contents($path);
            if ($content === false) {
                continue;
            }
            $styles .= \sprintf("<style>\n%s\n</style>\n", $content);
        }

        return $styles;
    }

    /**
     * Resolves a resource URL as an absolute local path, in the output directory.
     * Returns null if the resource is remote or is not in the output directory.
     */
    private function resolvePath(string $url, string $file, string $outputPath): ?string
    {
        $url = trim($url);
        if ($url === '' || str_starts_with($url, 'data:') || str_starts_with($url, '//') || Util\File::isRemote($url)) {
            return null;
        }
        $url = (string) parse_url($url, PHP_URL_PATH);
        if ($url === '') {
            return null;
        }
        $path = str_starts_with($url, '/')
            ? Util::joinFile($outputPath, rawurldecode($url))
            : Util::joinFile(\dirname($file), rawurldecode($url));
        $path = realpath($path);
        // must be in the output directory, to prevent path traversal
        if ($path === false || !is_file($path) || !str_starts_with($path, (string) realpath($outputPath))) {
            return null;
        }

        return $path;
    }

    /**
     * Converts the HTML document to PDF.
     *
     * @throws RuntimeException
     */
    private function render(string $html, string $outputPath, string $paper, string $orientation): string
    {
        $options = new Options();
        $options->setChroot($outputPath); // local resources are limited to the output directory
        $options->setIsRemoteEnabled(false); // remote resources are not allowed
        $options->setIsPhpEnabled(false);
        $options->setIsHtml5ParserEnabled(true);
        $dompdf = new Dompdf($options);
        $dompdf->setBasePath($outputPath . DIRECTORY_SEPARATOR);
        $dompdf->setPaper($paper, $orientation);
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->render();
        $pdf = $dompdf->output();
        if ($pdf === null) {
            throw new RuntimeException('Unable to create the PDF file.');
        }

        return $pdf;
    }
}
