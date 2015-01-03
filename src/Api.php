<?php
namespace PHPoole;

class Api
{
    public $api = '';

    public function __construct()
    {
        $this->api = 'API';
    }

    public function init($force)
    {
        return array(
            'OK 1',
            'OK 2' . (($force) ? ' (force)' : ''),
        );
    }
}