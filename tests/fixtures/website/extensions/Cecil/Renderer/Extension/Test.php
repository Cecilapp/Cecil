<?php

namespace Cecil\Renderer\Extension;

class Test extends \Twig\Extension\AbstractExtension
{
    public function getFilters()
    {
        return [
            new \Twig\TwigFilter('md5', 'md5'),
        ];
    }
}
