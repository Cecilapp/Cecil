<?php

declare(strict_types=1);

/*
 * This file is part of Cecil.
 *
 * Copyright (c) Arnaud Ligny <arnaud@ligny.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cecil\Step\Themes;

use Cecil\Exception\RuntimeException;
use Cecil\Step\AbstractStep;
use Cecil\Util;
use Symfony\Component\Yaml\Yaml;

/**
 * Imports (themes) configuration.
 */
class Import extends AbstractStep
{
    const THEME_CONFIG_FILE = 'config.yml';

    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return 'Importing themes configuration';
    }

    /**
     * {@inheritdoc}
     */
    public function init(array $options): void
    {
        if ($this->config->hasTheme()) {
            $this->canProcess = true;
        }
    }

    /**
     * {@inheritdoc}
     *
     * @throws RuntimeException
     */
    public function process(): void
    {
        $themes = array_reverse((array) $this->config->getTheme());
        $count = 0;
        $max = count($themes);
        foreach ($themes as $theme) {
            $count++;
            $themeConfigFile = $this->config->getThemesPath().'/'.$theme.'/'.self::THEME_CONFIG_FILE;
            $message = \sprintf('"%s": no configuration file', $theme);
            if (Util\File::getFS()->exists($themeConfigFile)) {
                if (false === $config = Util\File::fileGetContents($themeConfigFile)) {
                    throw new RuntimeException('Can\'t read the configuration file.');
                }
                $themeConfig = Yaml::parse($config);
                $this->config->import($themeConfig);
                $message = \sprintf('Theme "%s" imported', $theme);
            }

            $this->builder->getLogger()->info($message, ['progress' => [$count, $max]]);
        }
    }
}
