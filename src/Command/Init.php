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

        $this->wlInfo('Initializing new website');

        try {
            $messages = $this->_api->init($this->_force);
            foreach ($messages as $message) {
                $this->wlDone($message);
            }
        } catch (\Exception $e) {
            $this->wlError($e->getMessage());
        }
    }
}