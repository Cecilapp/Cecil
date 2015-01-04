<?php
namespace PHPoole\Command;

use PHPoole\Command\AbstractCommand;
use PHPoole;

class Init extends AbstractCommand
{
    /**
     * @var bool
     */
    protected $_force;

    public function processCommand()
    {
        $this->_force = $this->_route->getMatchedParam('force', false);

        $this->_console->write('Initializing new website...' . "\n");

        try {
            $messages = $this->_api->init($this->_force);
            foreach ($messages as $message) {
                $this->_console->write($message . "\n");
            }
        } catch (\Exception $e) {
            $this->_console->write($e->getMessage() . "\n");
        }
    }
}