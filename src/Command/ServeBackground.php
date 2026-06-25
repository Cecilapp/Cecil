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

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * ServeBackground command.
 *
 * Alias of `serve --background`: starts the built-in web server in background mode.
 */
class ServeBackground extends Serve
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        parent::configure();

        $this
            ->setName('serve:background')
            ->setDescription('Starts the built-in server in the background (alias of `serve --background`)')
            ->setHelp(
                <<<'EOF'
The <info>%command.name%</> command starts the built-in web server in the background.

  <info>%command.full_name%</>
  <info>%command.full_name% path/to/the/working/directory</>

This command is an alias of <info>serve --background</>.

Stop the server with:

  <info>%bin_name% serve:stop</>
EOF
            );
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $input->setOption('background', true);

        return parent::execute($input, $output);
    }
}
