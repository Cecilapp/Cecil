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

namespace Cecil\Test\Unit\Renderer;

use Cecil\Builder;
use Cecil\Renderer\Twig;
use Cecil\Util;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class TwigTest extends TestCase
{
    private string $sourceDir;

    protected function setUp(): void
    {
        $this->sourceDir = Util::joinFile(sys_get_temp_dir(), 'cecil-test-twig-' . bin2hex(random_bytes(6)));
        Util\File::getFS()->mkdir(Util::joinFile($this->sourceDir, 'layouts'));
    }

    protected function tearDown(): void
    {
        Util\File::getFS()->remove($this->sourceDir);
    }

    /**
     * Minify filters output code, which must not be escaped by the
     * extension-based autoescape strategy (`layouts.autoescape: null`).
     *
     * @return array<string, array{string, string, string}>
     */
    public static function minifyAutoescapeProvider(): array
    {
        return [
            'minify_js in .js template' => [
                'test.js.twig',
                "{% apply minify_js %}if ('serviceWorker' in navigator) { navigator.serviceWorker.register('/sw.js'); }{% endapply %}",
                "if('serviceWorker' in navigator){navigator.serviceWorker.register('/sw.js')}",
            ],
            'minify_js in .html template' => [
                'test.html.twig',
                "<script>{% apply minify_js %}if ('serviceWorker' in navigator) { navigator.serviceWorker.register('/sw.js'); }{% endapply %}</script>",
                "<script>if('serviceWorker' in navigator){navigator.serviceWorker.register('/sw.js')}</script>",
            ],
            'minify_css in .css template' => [
                'test.css.twig',
                "{% apply minify_css %}a > b { content: \"x\"; font-family: 'Foo'; }{% endapply %}",
                "a>b{content:\"x\";font-family:'Foo'}",
            ],
            'minify_css in .html template' => [
                'test.html.twig',
                "<style>{% apply minify_css %}a > b { content: \"x\"; font-family: 'Foo'; }{% endapply %}</style>",
                "<style>a>b{content:\"x\";font-family:'Foo'}</style>",
            ],
        ];
    }

    #[DataProvider('minifyAutoescapeProvider')]
    public function testMinifyFiltersAreNotEscapedWithAutoescapeStrategy(string $template, string $source, string $expected): void
    {
        self::assertSame($expected, $this->render($template, $source));
    }

    public function testVariablesAreStillEscapedWithAutoescapeStrategy(): void
    {
        self::assertSame('&lt;b&gt;', $this->render('test.html.twig', '{{ value }}', ['value' => '<b>']));
        self::assertSame('\u003Cb\u003E', $this->render('test.js.twig', '{{ value }}', ['value' => '<b>']));
    }

    /**
     * Compiled templates depend on the autoescape strategy: changing
     * `layouts.autoescape` between builds must not reuse templates
     * compiled (and cached) with the previous strategy.
     */
    public function testTemplatesCacheDependsOnAutoescapeStrategy(): void
    {
        $variables = ['value' => "L'atelier <b>"];
        $raw = "L'atelier <b>";
        $escaped = 'L&#039;atelier &lt;b&gt;';

        self::assertSame($raw, $this->render('test.html.twig', '{{ value }}', $variables, false, true));
        self::assertSame($escaped, $this->render('test.html.twig', '{{ value }}', $variables, null, true));
        self::assertSame($escaped, $this->render('test.html.twig', '{{ value }}', $variables, 'html', true));
        self::assertSame($raw, $this->render('test.html.twig', '{{ value }}', $variables, false, true));
    }

    /**
     * Renders a template (with `layouts.autoescape: null` by default).
     *
     * @param array<string, mixed> $variables
     */
    private function render(string $template, string $source, array $variables = [], string|false|null $autoescape = null, bool $cache = false): string
    {
        $layoutsDir = Util::joinFile($this->sourceDir, 'layouts');
        Util\File::getFS()->dumpFile(Util::joinFile($layoutsDir, $template), $source);

        $builder = new Builder([
            'cache'   => ['enabled' => $cache, 'templates' => $cache],
            'layouts' => ['autoescape' => $autoescape],
        ]);
        $builder->setSourceDir($this->sourceDir);
        $builder->setDestinationDir($this->sourceDir);

        return (new Twig($builder, $layoutsDir))->render($template, $variables);
    }
}
