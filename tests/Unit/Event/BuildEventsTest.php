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

namespace Cecil\Test\Unit\Event;

use Cecil\Builder;
use Cecil\Event\AbstractSubscriber;
use Cecil\Event\BuildEvent;
use Cecil\Event\BuildEvents;
use Cecil\Event\PageEvent;
use Cecil\Event\PageRenderedEvent;
use Cecil\Event\PageSavedEvent;
use Cecil\Event\StepEvent;
use Cecil\Exception\RuntimeException;
use Cecil\Util;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Symfony\Component\Filesystem\Filesystem;

class BuildEventsTest extends TestCase
{
    private string $sourceDir;

    private Filesystem $filesystem;

    protected function setUp(): void
    {
        $this->filesystem = new Filesystem();
        $this->sourceDir = Util::joinFile(sys_get_temp_dir(), 'cecil-events-' . uniqid('', true));
        $this->filesystem->dumpFile(Util::joinFile($this->sourceDir, 'pages', 'test.md'), "---\ntitle: Test\n---\nBody");
        RecorderSubscriber::$events = [];
    }

    protected function tearDown(): void
    {
        $this->filesystem->remove($this->sourceDir);
    }

    public function testSubscriberFromConfigurationReceivesEventsInOrder(): void
    {
        $builder = $this->createBuilder(['extensions' => ['subscribers' => ['Recorder' => RecorderSubscriber::class]]]);
        $builder->build([]);

        $events = RecorderSubscriber::$events;
        self::assertSame(BuildEvents::BUILD_START, $events[0]);
        self::assertSame(BuildEvents::BUILD_FINISHED, end($events));
        self::assertContains('page.created:test', $events);
        self::assertContains('page.converted:test', $events);
        self::assertContains('page.rendered:test:html', $events);
        self::assertContains('page.saved:test:html', $events);
        self::assertContains('step.pre:Generating pages', $events);
        // each step is surrounded by its pre and post events
        $steps = array_values(array_filter($events, fn ($event) => str_starts_with($event, 'step.')));
        self::assertCount(\count($builder->getMetrics()['steps']) * 2, $steps);
        self::assertLessThan(array_search('step.post:Creating pages', $events), array_search('page.created:test', $events));
        self::assertLessThan(array_search('page.saved:test:html', $events), array_search('page.rendered:test:html', $events));
    }

    public function testRenderedOutputCanBeModified(): void
    {
        $builder = $this->createBuilder(['extensions' => ['subscribers' => ['Recorder' => RecorderSubscriber::class]]]);
        $builder->build([]);

        $output = (string) file_get_contents(Util::joinFile($this->sourceDir, '_site', 'test', 'index.html'));
        self::assertStringEndsWith('<!-- recorded -->', $output);
    }

    public function testListenerCanBeAddedToTheDispatcher(): void
    {
        $builder = $this->createBuilder();
        $saved = [];
        $builder->getEventDispatcher()->addListener(BuildEvents::PAGE_SAVED, function (PageSavedEvent $event) use (&$saved) {
            $saved[] = $event->getPathname();
        });
        $builder->build([]);

        self::assertContains(Util::joinFile($this->sourceDir, '_site', 'test', 'index.html'), $saved);
    }

    public function testSubscribersAreLoadedOnce(): void
    {
        $builder = $this->createBuilder(['extensions' => ['subscribers' => ['Recorder' => RecorderSubscriber::class]]]);
        $builder->build(['dry-run' => true]);
        $builder->build(['dry-run' => true]);

        self::assertCount(2, array_keys(RecorderSubscriber::$events, BuildEvents::BUILD_START, true));
    }

    public function testInvalidSubscriberFailsTheBuild(): void
    {
        $builder = $this->createBuilder(['extensions' => ['subscribers' => ['Invalid' => \stdClass::class]]]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Unable to load extension "Invalid" (extensions.subscribers)');

        $builder->build(['dry-run' => true]);
    }

    /**
     * @param array<string, mixed> $config
     */
    private function createBuilder(array $config = []): Builder
    {
        $config = array_replace_recursive(['baseurl' => 'https://example.com/', 'cache' => false], $config);

        return (new Builder($config, new NullLogger()))->setSourceDir($this->sourceDir);
    }
}

/**
 * Records dispatched events.
 */
class RecorderSubscriber extends AbstractSubscriber
{
    /** @var list<string> */
    public static array $events = [];

    public static function getSubscribedEvents(): array
    {
        return [
            BuildEvents::BUILD_START    => 'onBuild',
            BuildEvents::BUILD_FINISHED => 'onBuild',
            BuildEvents::STEP_PRE       => 'onStep',
            BuildEvents::STEP_POST      => 'onStep',
            BuildEvents::PAGE_CREATED   => 'onPage',
            BuildEvents::PAGE_CONVERTED => 'onPage',
            BuildEvents::PAGE_RENDERED  => 'onPageRendered',
            BuildEvents::PAGE_SAVED     => 'onPageSaved',
        ];
    }

    public function onBuild(BuildEvent $event, string $name): void
    {
        self::$events[] = $name;
    }

    public function onStep(StepEvent $event, string $name): void
    {
        self::$events[] = $name . ':' . $event->getStep()->getName();
    }

    public function onPage(PageEvent $event, string $name): void
    {
        self::$events[] = $name . ':' . $event->getPage()->getId();
    }

    public function onPageRendered(PageRenderedEvent $event, string $name): void
    {
        self::$events[] = $name . ':' . $event->getPage()->getId() . ':' . $event->getFormat();
        if ($event->getFormat() === 'html') {
            $event->setOutput(rtrim($event->getOutput()) . '<!-- recorded -->');
        }
    }

    public function onPageSaved(PageSavedEvent $event, string $name): void
    {
        self::$events[] = $name . ':' . $event->getPage()->getId() . ':' . $event->getFormat();
    }
}
