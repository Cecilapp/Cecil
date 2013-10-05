<?php

Class Plugin1 extends PHPoole_Plugin
{
    public function preInit($e)
    {
        $event  = $e->getName();
        $params = $e->getParams();
        $this->debug($event, $params, 'IN');
        // force bootstrap
        $params['type'] = 'bootstrap';
        $this->debug($event, $params, 'OUT');
        return $params;
    }
}