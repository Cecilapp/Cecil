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

class Build extends AbstractCommand
{
    /**
     * @var bool
     */
    protected $_serve;

    public function processCommand()
    {
        $this->_serve = $this->_route->getMatchedParam('serve', false);
        if ($this->_serve) {
            $this->getPhpoole()->setLocalServe();
        } else {
            $this->getPhpoole()->setLocalServe(false);
        }

        $this->wlAnnonce('Building website:');
        try {
            $this->getPhpoole()
                ->isPhpoole()
                ->loadPages()
                ->build();
            $messages = $this->getPhpoole()->getMessages();
            foreach ($messages as $message) {
                $this->wlDone($message);
            }
        } catch (\Exception $e) {
            $this->wlError($e->getMessage());
        }
        if ($this->_serve) {
            $this->wlAlert('You should re-build before deploy');
            $callable = new Serve;
            call_user_func($callable, $this->getRoute(), $this->getConsole());
        }
    }
}