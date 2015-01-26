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
use PHPoole\Command\Serve;

class Generate extends AbstractCommand
{
    /**
     * @var bool
     */
    protected $_serve;

    public function processCommand()
    {
        $this->_serve = $this->_route->getMatchedParam('serve', false);
        if ($this->_serve) {
            $this->_phpoole->setLocalServe();
        } else {
            $this->_phpoole->setLocalServe(false);
        }

        $this->wlAnnonce('Generating website:');
        try {
            $this->_phpoole->loadPages()->generate();
            $messages = $this->_phpoole->getMessages();
            foreach ($messages as $message) {
                $this->wlDone($message);
            }
        } catch (\Exception $e) {
            $this->wlError($e->getMessage());
        }
        if ($this->_serve) {
            $this->wlAlert('You should re-generate before deploy');
            $callable = new Serve;
            call_user_func($callable, $this->_route, $this->_console);
        }
    }
}