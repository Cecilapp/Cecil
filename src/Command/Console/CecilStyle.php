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

namespace Cecil\Command\Console;

use Symfony\Component\Console\Formatter\OutputFormatter;
use Symfony\Component\Console\Style\SymfonyStyle;

final class CecilStyle extends SymfonyStyle
{
    public function title(string $message): void
    {
        $this->autoPrependBlock();
        $this->writeln([\sprintf('<fg=yellow># %s</>', OutputFormatter::escapeTrailingBackslash($message))]);
        $this->newLine();
    }

    private function autoPrependBlock(): void
    {
        $chars = substr(str_replace(\PHP_EOL, "\n", $this->getBufferedOutput()->fetch()), -2);

        if (!isset($chars[0])) {
            $this->newLine();

            return;
        }

        $this->newLine(2 - substr_count($chars, "\n"));
    }

    private function getBufferedOutput(): object
    {
        static $property = null;

        if (null === $property) {
            $property = (new \ReflectionClass(SymfonyStyle::class))->getProperty('bufferedOutput');
            $property->setAccessible(true);
        }

        return $property->getValue($this);
    }
}
