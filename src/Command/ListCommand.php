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

/**
 * ListCommand class.
 *
 * This command is a hidden version of the Symfony Console ListCommand.
 * It is used to provide a list of commands without displaying it in the help output.
 * This can be useful for internal purposes or when you want to keep the command list hidden from the user.
 */
class ListCommand extends \Symfony\Component\Console\Command\ListCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        parent::configure();
        $this->setHidden(true);
    }
}
