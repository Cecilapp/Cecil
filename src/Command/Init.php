<?php
/*
 * This file is part of the PHPoole package.
 *
 * Copyright (c) Arnaud Ligny <arnaud@ligny.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PHPoole\Command;

use PHPoole\Command\AbstractCommand;

class Init extends AbstractCommand
{
    /**
     * @var bool
     */
    protected $_force;

    public function processCommand()
    {
        $this->_force = $this->getRoute()->getMatchedParam('force', false);

        $this->wlAnnonce('Initializing new website:');
        try {
            $messages = $this->getPhpoole()->init($this->_force);
            foreach ($messages as $message) {
                $this->wlDone($message);
            }
        } catch (\Exception $e) {
            $this->wlError($e->getMessage());
        }
    }
}