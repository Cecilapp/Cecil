<?php
/*
 * Copyright (c) Arnaud Ligny <arnaud@ligny.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cecil\Step;

use Cecil\Util;
use Symfony\Component\Yaml\Yaml;

/**
 * Import (themes) config.
 */
class ConfigImport extends AbstractStep
{
    const THEME_CONFIG_FILE = 'config.yml';

    /**
     * {@inheritdoc}
     */
    public function init($options)
    {
        if ($this->config->hasTheme()) {
            $this->process = true;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function process()
    {
        call_user_func_array($this->builder->getMessageCb(), ['CONFIG', 'Importing config']);

        $themes = array_reverse($this->config->getTheme());
        $count = 0;
        $max = count($themes);
        foreach ($themes as $theme) {
            $count++;
            $themeConfigFile = $this->config->getThemesPath().'/'.$theme.'/'.self::THEME_CONFIG_FILE;
            if (Util::getFS()->exists($themeConfigFile)) {
                set_error_handler(
                    function ($severity, $message, $file, $line) {
                        throw new \ErrorException($message, 0, $severity, $file, $line, null);
                    }
                );
                $config = file_get_contents($themeConfigFile);
                restore_error_handler();
                $themeConfig = Yaml::parse($config);
                $this->config->import($themeConfig);
                $message = sprintf('"%s" theme: config imported', $theme);
            } else {
                $message = sprintf('"%s" theme: no config file', $theme);
            }
            call_user_func_array($this->builder->getMessageCb(), ['CONFIG_PROGRESS', $message, $count, $max]);
        }
    }
}
