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
use Symfony\Component\Finder\Finder;

/**
 * Load data files.
 */
class DataLoad extends AbstractStep
{
    /**
     * {@inheritdoc}
     */
    public function init($options)
    {
        if (is_dir($this->builder->getConfig()->getDataPath())) {
            $this->process = true;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function process()
    {
        call_user_func_array($this->builder->getMessageCb(), ['DATA', 'Loading data']);

        $data = Finder::create()
            ->files()
            ->in($this->builder->getConfig()->getDataPath())
            ->name('/\.(' . implode('|', $this->builder->getConfig()->get('data.ext')) . ')$/')
            ->sortByName(true);

        $count = 0;
        $max = count($data);

        /* @var $file \Symfony\Component\Finder\SplFileInfo */
        foreach ($data as $file) {
            $count++;
            set_error_handler(
                function ($severity, $message, $file, $line) {
                    throw new \ErrorException($message, 0, $severity, $file, $line, null);
                }
            );
            $dataFile = $file->getContents();
            restore_error_handler();
            $dataArray = Yaml::parse($dataFile);
            $message = sprintf('"%s" loaded', $file->getBasename());
            $dataArray = array_merge_recursive(
                $this->builder->getData(),
                [$file->getBasename('.' . $file->getExtension()) => $dataArray]
            );
            $this->builder->setData($dataArray);

            call_user_func_array($this->builder->getMessageCb(), ['DATA_PROGRESS', $message, $count, $max]);
        }
    }
}
