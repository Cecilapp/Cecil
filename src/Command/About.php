<?php

declare(strict_types=1);

/*
 * This file is part of Cecil.
 *
 * Copyright (c) Arnaud Ligny <arnaud@ligny.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cecil\Command;

use Cecil\Builder;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * About Cecil.
 */
class About extends AbstractCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('about')
            ->setDescription('Shows a short description about Cecil')
            ->setHelp(
                <<<'EOF'
The <info>%command.name%</> command displays a short description about Cecil.
EOF
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $version = Builder::VERSION;

        $this->io->text([
"<info>Cecil - A simple and powerful content-driven static site generator - version $version</>",
"See <href=https://cecil.app>https://cecil.app</> for more information."
        ]);

        return 0;
    }
}
