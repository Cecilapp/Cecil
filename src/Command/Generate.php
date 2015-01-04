<?php
namespace PHPoole\Command;

use PHPoole\Command\AbstractCommand;
use PHPoole;

class Generate extends AbstractCommand
{
    /**
     * @var bool
     */
    protected $_serve;

    public function processCommand()
    {
        $this->_serve = $this->_route->getMatchedParam('serve', false);

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

        if ($this->_serve) {
            $callable = new PHPoole\Command\Serve;
            call_user_func($callable, $this->_route, $this->_console);
        }
    }
}