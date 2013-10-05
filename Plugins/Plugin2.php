<?php

Class Plugin2 extends PHPoole_Plugin
{
    public function postInit($e)
    {
        $event  = $e->getName();
        $params = $e->getParams();
        $this->debug($event, $params);
        echo "init done!\n";
        return $e;
    }
}