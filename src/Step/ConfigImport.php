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
 * Imports (themes) configuration.
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
        $this->builder->getLogger()->debug('Importing configuration');

        $themes = array_reverse((array) $this->config->getTheme());
        $count = 0;
        $max = count($themes);
        foreach ($themes as $theme) {
            $count++;
            $themeConfigFile = $this->config->getThemesPath().'/'.$theme.'/'.self::THEME_CONFIG_FILE;
            $message = sprintf('"%s": no configuration file', $theme);
            if (Util::getFS()->exists($themeConfigFile)) {
                $config = Util::fileGetContents($themeConfigFile);
                if ($config === false) {
                    throw new \Exception('Can\'t read the configuration file.');
                }
                $themeConfig = Yaml::parse($config);
                $this->config->import($themeConfig);
                $message = sprintf('"%s": imported', $theme);
            }

            $this->builder->getLogger()->debug($message, ['progress' => [$count, $max]]);
        }
    }
}
