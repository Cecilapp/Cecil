<?php
namespace PHPoole\Command;

use PHPoole\Command\AbstractCommand;
use PHPoole;

class Generate extends AbstractCommand
{
    public function processCommand()
    {
        $this->wlInfo('Generating website');

        try {
            $this->_api->loadPages()->generate();
            $messages = $this->_api->getMessages();
            foreach ($messages as $message) {
                $this->wlDone($message);
            }
        } catch (\Exception $e) {
            $this->wlError($e->getMessage());
        }
    }
}