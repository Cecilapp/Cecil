<?php
namespace PHPoole\Command;

use PHPoole\Command\AbstractCommand;
use PHPoole;

class Generate extends AbstractCommand
{
    public function processCommand()
    {
        $this->_console->write('Generating website...' . "\n");

        try {
            $this->_api->loadPages()->generate();
            $messages = $this->_api->getMessages();
            foreach ($messages as $message) {
                $this->_console->write($message . "\n");
            }
        } catch (\Exception $e) {
            $this->_console->write($e->getMessage() . "\n");
        }
    }
}