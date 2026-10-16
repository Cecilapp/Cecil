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

namespace Cecil\Test\Unit\Converter;

use Cecil\Builder;
use Cecil\Collection\Page\Collection as PagesCollection;
use Cecil\Converter\Parsedown;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Component\Filesystem\Filesystem;

class ParsedownTest extends TestCase
{
    private string $root;

    private Filesystem $filesystem;

    private Parsedown $parsedown;

    private Builder $builder;

    private string $sourceDir;

    protected function setUp(): void
    {
        $this->filesystem = new Filesystem();
        $this->root = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'cecil-parsedown-test-' . uniqid('', true);
        $sourceDir = $this->sourceDir = $this->root . DIRECTORY_SEPARATOR . 'source';
        $destinationDir = $this->root . DIRECTORY_SEPARATOR . 'destination';
        $this->filesystem->mkdir([$sourceDir, $destinationDir]);
        $this->filesystem->dumpFile($sourceDir . '/assets/videos/test.mp4', 'video');
        $this->filesystem->dumpFile($sourceDir . '/assets/audio/test.mp3', 'audio');

        $builder = new Builder([
            'baseurl' => 'https://example.com/',
            'cache' => ['enabled' => false],
            'assets' => ['fingerprint' => true],
        ], new NullLogger());
        $builder->setSourceDir($sourceDir);
        $builder->setDestinationDir($destinationDir);
        $builder->setPages(new PagesCollection('pages'));
        // the build ID is normally set by Builder::build() (and resets Parsedown static caches)
        self::setBuildId(uniqid('test', true));
        $this->parsedown = new Parsedown($builder);
        $this->builder = $builder;
    }

    protected function tearDown(): void
    {
        self::setBuildId(null);
        $this->filesystem->remove($this->root);
    }

    private static function setBuildId(?string $id): void
    {
        \Closure::bind(static function (?string $id): void {
            self::$buildId = $id;
        }, null, Builder::class)($id);
    }

    public function testNotEmbeddedLocalVideoLinkTargetsTheFingerprintedAsset(): void
    {
        $html = $this->parsedown->text('[Video](/videos/test.mp4){embed=false}');

        self::assertMatchesRegularExpression('#<a href="/videos/test\.[a-f0-9]{32}\.mp4">Video</a>#', $html);
    }

    public function testNotEmbeddedLocalAudioLinkTargetsTheFingerprintedAsset(): void
    {
        $html = $this->parsedown->text('[Audio](/audio/test.mp3){embed=false}');

        self::assertMatchesRegularExpression('#<a href="/audio/test\.[a-f0-9]{32}\.mp3">Audio</a>#', $html);
    }

    public function testNotEmbeddedMissingVideoLinkIsKeptAsIs(): void
    {
        $html = $this->parsedown->text('[Video](/videos/missing.mp4){embed=false}');

        self::assertStringContainsString('<a href="/videos/missing.mp4">Video</a>', $html);
    }

    public function testNoteWithoutTemplateIsAnAside(): void
    {
        $html = $this->parsedown->text(":::tip\n**Tip:** advice.\n:::");

        self::assertSame('<aside class="note note-tip"><p><strong>Tip:</strong> advice.</p></aside>', $html);
    }

    public function testTabsNoteIsSplitOnHeadings(): void
    {
        $html = $this->parsedown->text(":::tabs\nIntro.\n\n#### macOS\n\n```bash\n#### not a heading\n```\n\n#### Windows\n\nUse **scoop**.\n:::");

        self::assertStringContainsString('<div class="note note-tabs" id="note-tabs-1">', $html);
        self::assertStringContainsString('<p>Intro.</p>', $html);
        self::assertSame(2, substr_count($html, '<section class="note-tab"'));
        self::assertStringContainsString('<h4>macOS</h4>', $html);
        self::assertStringContainsString('#### not a heading', $html);
        self::assertStringContainsString('<h4>Windows</h4>', $html);
        self::assertStringNotContainsString('<aside', $html);
    }

    public function testCodeGroupNoteIsSplitOnCodeBlocks(): void
    {
        $html = $this->parsedown->text(":::code-group\n```bash\nnpm i\n```\n\n```php\necho 1;\n```\n:::");

        self::assertStringContainsString('<p class="note-tab-title">bash</p>', $html);
        self::assertStringContainsString('<p class="note-tab-title">php</p>', $html);
        self::assertSame(2, substr_count($html, '<pre>'));
    }

    public function testCardsNoteIsSplitOnListEntries(): void
    {
        $html = $this->parsedown->text(":::cards\n- [Install](https://example.com/install/): first steps\n- No link\n:::");

        self::assertStringContainsString('<p class="note-card-title"><a href="https://example.com/install/">Install</a></p>', $html);
        self::assertStringContainsString('<div class="note-card-content">first steps</div>', $html);
        self::assertStringContainsString('<div class="note-card-content">No link</div>', $html);
    }

    public function testAccordionNoteKeepsHeadingId(): void
    {
        $html = $this->parsedown->text(":::accordion\n#### Question {#q1}\nAnswer.\n:::");

        self::assertStringContainsString('<details class="note-accordion-item" id="q1">', $html);
        self::assertStringContainsString('<summary>Question</summary>', $html);
        self::assertStringContainsString('<p>Answer.</p>', $html);
    }

    public function testStepsNoteIsAnOrderedList(): void
    {
        $html = $this->parsedown->text(":::steps\n#### One\nDo it.\n#### Two\nDone.\n:::");

        self::assertStringContainsString('<ol class="note-steps-list">', $html);
        self::assertSame(2, substr_count($html, '<li class="note-step">'));
    }

    public function testNestedNoteInALongerFence(): void
    {
        $html = $this->parsedown->text("::::info\nBefore.\n\n:::tip\nAdvice.\n:::\n\nAfter.\n::::\n\nOutside.");

        self::assertSame('<aside class="note note-info"><p>Before.</p>' . "\n" . '<aside class="note note-tip"><p>Advice.</p></aside>' . "\n" . '<p>After.</p></aside>' . "\n" . '<p>Outside.</p>', $html);
    }

    public function testNestedNotesOnTwoLevels(): void
    {
        $html = $this->parsedown->text(":::::info\n::::tip\n:::warning\nDeep.\n:::\n::::\nEnd.\n:::::");

        self::assertSame(1, substr_count($html, '<aside class="note note-info">'));
        self::assertSame(1, substr_count($html, '<aside class="note note-tip">'));
        self::assertSame(1, substr_count($html, '<aside class="note note-warning"><p>Deep.</p></aside>'));
        self::assertStringEndsWith('<p>End.</p></aside>', $html);
    }

    public function testNoteIsClosedByALongerFence(): void
    {
        $html = $this->parsedown->text(":::tip\nAdvice.\n::::\n\nOutside.");

        self::assertSame('<aside class="note note-tip"><p>Advice.</p></aside>' . "\n" . '<p>Outside.</p>', $html);
    }

    public function testNoteInALongerFenceIsNotClosedInACodeBlock(): void
    {
        $html = $this->parsedown->text("::::info\n```md\n:::tip\n:::\n```\n::::\n\nOutside.");

        self::assertStringStartsWith('<aside class="note note-info"><pre><code', $html);
        self::assertStringContainsString('>:::tip' . "\n" . ':::</code></pre></aside>', $html);
        self::assertStringNotContainsString('<aside class="note note-tip">', $html);
        self::assertStringEndsWith('<p>Outside.</p>', $html);
    }

    public function testTabsNoteWithNestedNote(): void
    {
        $html = $this->parsedown->text("::::tabs\n#### macOS\n\n:::accordion\n#### Question\nAnswer.\n:::\n\n#### Windows\n\n:::tip\nAdvice.\n:::\n::::");

        self::assertStringContainsString('<div class="note note-tabs" id="note-tabs-', $html);
        self::assertSame(2, substr_count($html, '<section class="note-tab"'));
        self::assertStringContainsString('<details class="note-accordion-item"', $html);
        self::assertStringContainsString('<summary>Question</summary>', $html);
        self::assertStringContainsString('<aside class="note note-tip"><p>Advice.</p></aside>', $html);
    }

    public function testNoteTemplateCanBeOverridden(): void
    {
        $this->filesystem->dumpFile($this->sourceDir . '/layouts/partials/notes/tip.html.twig', '<div class="tip">{{ note.content }}</div>');
        self::setBuildId(uniqid('test', true));
        $parsedown = new Parsedown($this->builder);

        $html = $parsedown->text(":::tip\nadvice\n:::");

        self::assertSame('<div class="tip"><p>advice</p></div>', $html);
    }

    public function testNoteTemplateErrorFallsBackToTheNoteContent(): void
    {
        $this->filesystem->dumpFile($this->sourceDir . '/layouts/partials/notes/tip.html.twig', '{{ site.title }}');
        self::setBuildId(uniqid('test', true));
        $parsedown = new Parsedown($this->builder);

        $html = $parsedown->text(":::tip\nadvice\n:::");

        self::assertSame('<aside class="note note-tip"><p>advice</p></aside>', $html);
    }

    public function testFencedCodeIsHighlightedWithHighlightJsByDefault(): void
    {
        $html = $this->parsedown->text("```php\necho 1;\n```");

        self::assertStringContainsString('<code class="language-php hljs php" translate="no">', $html);
        self::assertStringContainsString('<span class="hljs-keyword">echo</span>', $html);
    }

    public function testFencedCodeIsHighlightedWithConfiguredEngine(): void
    {
        $this->builder->getConfig()->import(['pages' => ['body' => ['highlight' => ['engine' => 'tempest']]]]);
        $parsedown = new Parsedown($this->builder);

        $html = $parsedown->text("```php\necho 1;\n```");

        self::assertStringContainsString('<code class="language-php hl" translate="no">', $html);
        self::assertStringContainsString('<span class="hl-keyword">echo</span>', $html);
    }

    public function testFencedCodeWithUnknownLanguageIsNotHighlighted(): void
    {
        $html = $this->parsedown->text("```unknown-language\na < b\n```");

        self::assertStringContainsString('<code class="language-unknown-language" translate="no">a &lt; b', $html);
    }

    public function testFencedCodeIsNotHighlightedWhenDisabled(): void
    {
        $this->builder->getConfig()->import(['pages' => ['body' => ['highlight' => ['enabled' => false, 'engine' => 'tempest']]]]);
        $parsedown = new Parsedown($this->builder);

        $html = $parsedown->text("```php\necho 1;\n```");

        self::assertStringContainsString('<code class="language-php" translate="no">echo 1;', $html);
    }
}
