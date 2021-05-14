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
    public function getName(): string
    {
        return 'Importing configuration';
    }

    /**
     * {@inheritdoc}
     */
    public function init($options)
    {
        if ($this->config->hasTheme()) {
            $this->canProcess = true;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function process()
    {
        $themes = array_reverse((array) $this->config->getTheme());
        $count = 0;
        $max = count($themes);
        foreach ($themes as $theme) {
            $count++;
            $themeConfigFile = $this->config->getThemesPath().'/'.$theme.'/'.self::THEME_CONFIG_FILE;
            $message = sprintf('"%s": no configuration file', $theme);
            if (Util\File::getFS()->exists($themeConfigFile)) {
                $config = Util\File::fileGetContents($themeConfigFile);
                if ($config === false) {
                    throw new \Exception('Can\'t read the configuration file.');
                }
                $themeConfig = Yaml::parse($config);
                $this->config->import($themeConfig);
                $message = sprintf('"%s": imported', $theme);
            }

            $this->builder->getLogger()->info($message, ['progress' => [$count, $max]]);
        }
    }
}
