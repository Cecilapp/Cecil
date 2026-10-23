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

namespace Cecil\Test\Unit\Command;

use Cecil\Application;
use Cecil\Command\NewSite;
use Cecil\Exception\RuntimeException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Yaml\Yaml;

class NewSiteTest extends TestCase
{
    private string $tmpDir;
    private Filesystem $fs;
    private CommandTester $tester;

    protected function setUp(): void
    {
        $this->fs = new Filesystem();
        $this->tmpDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'cecil-newsite-test-' . uniqid('', true);
        $this->fs->mkdir($this->tmpDir);
        $this->tester = new CommandTester((new Application())->find('new:site'));
    }

    protected function tearDown(): void
    {
        $this->fs->remove($this->tmpDir);
    }

    public function testDefaultsWithoutInteraction(): void
    {
        $this->tester->execute(['path' => $this->tmpDir], ['interactive' => false]);

        $this->tester->assertCommandIsSuccessful();
        $yaml = $this->readFile('cecil.yml');
        self::assertStringContainsString("title: 'New website'", $yaml);
        self::assertStringContainsString("language: en\n", $yaml);
        self::assertStringContainsString('#author:', $yaml); // commented example is kept
        self::assertStringNotContainsString('languages:', $yaml);
        foreach (['README.md', '.gitignore', 'assets/favicon.png', 'assets/icon.png', 'pages/index.md', 'static/favicon.ico'] as $file) {
            self::assertFileExists($this->path($file));
        }
        self::assertFileDoesNotExist($this->path('gitignore'));
        self::assertDirectoryDoesNotExist($this->path('.github'));
        self::assertDirectoryDoesNotExist($this->path('pages/blog'));
    }

    public function testInteractiveAnswers(): void
    {
        // title, baseline, base URL, description, author name, author URL, language, demo, deploy
        $this->tester->setInputs(['My site', 'My baseline', 'https://example.com', 'My description', 'Jane Doe', 'https://jane.example', 'de', 'no', 'yes']);
        $this->tester->execute(['path' => $this->tmpDir]);

        $this->tester->assertCommandIsSuccessful();
        $config = Yaml::parseFile($this->path('cecil.yml'));
        self::assertSame('My site', $config['title']);
        self::assertSame('My baseline', $config['baseline']);
        self::assertSame('https://example.com/', $config['baseurl']);
        self::assertSame('My description', $config['description']);
        self::assertSame(['name' => 'Jane Doe', 'url' => 'https://jane.example/'], $config['author']);
        self::assertSame('de', $config['language']);
        self::assertSame(['de'], array_column($config['languages'], 'code'));
        self::assertSame('de', $config['languages'][0]['locale']);
        self::assertFileExists($this->path('.github/workflows/build-and-deploy.yml'));
        self::assertDirectoryDoesNotExist($this->path('pages/blog'));
    }

    public function testDemoAddsTheDemoLanguage(): void
    {
        $this->tester->execute(['path' => $this->tmpDir, '--demo' => true], ['interactive' => false]);

        $this->tester->assertCommandIsSuccessful();
        $config = Yaml::parseFile($this->path('cecil.yml'));
        self::assertSame('en', $config['language']);
        self::assertSame(['en', NewSite::DEMO_LANGUAGE['code']], array_column($config['languages'], 'code'));
        foreach (['pages/blog/welcome.md', 'pages/blog/welcome.fr.md', 'layouts/page.html.twig', 'assets/css/style.scss', 'data/links.yaml', 'translations/messages.fr.yaml'] as $file) {
            self::assertFileExists($this->path($file));
        }
    }

    public function testDemoWithTheDemoLanguageAsMainLanguage(): void
    {
        // title, baseline, base URL, description, author name, language, deploy
        $this->tester->setInputs(['', '', '', '', '', 'fr-FR', 'no']);
        $this->tester->execute(['path' => $this->tmpDir, '--demo' => true]);

        $this->tester->assertCommandIsSuccessful();
        $config = Yaml::parseFile($this->path('cecil.yml'));
        self::assertSame('fr-FR', $config['language']);
        self::assertSame(['fr-FR'], array_column($config['languages'], 'code'));
        self::assertSame('fr_FR', $config['languages'][0]['locale']);
        self::assertFileExists($this->path('pages/blog/welcome.md'));
        self::assertFileDoesNotExist($this->path('pages/blog/welcome.fr.md'));
    }

    public function testExistingProjectFilesArePreserved(): void
    {
        $this->fs->dumpFile($this->path('README.md'), 'My README');
        $this->fs->dumpFile($this->path('.gitignore'), 'vendor/');

        $this->tester->execute(['path' => $this->tmpDir], ['interactive' => false]);

        $this->tester->assertCommandIsSuccessful();
        self::assertSame('My README', $this->readFile('README.md'));
        self::assertSame('vendor/', $this->readFile('.gitignore'));
    }

    public function testInvalidLanguageCode(): void
    {
        self::assertSame('fr-FR', NewSite::validateLanguageCode('fr-FR'));
        $this->expectException(RuntimeException::class);
        NewSite::validateLanguageCode('french');
    }

    private function path(string $file): string
    {
        return $this->tmpDir . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $file);
    }

    private function readFile(string $file): string
    {
        return str_replace("\r\n", "\n", (string) file_get_contents($this->path($file)));
    }
}
