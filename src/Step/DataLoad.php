<?php
/*
 * Copyright (c) Arnaud Ligny <arnaud@ligny.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cecil\Step;

use Symfony\Component\Finder\Finder;
use Symfony\Component\Yaml\Yaml;

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
            ->name('/\.('.implode('|', $this->builder->getConfig()->get('data.ext')).')$/')
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

            $basename = $file->getBasename('.' . $file->getExtension());
            $subpath = \Cecil\Util::getFS()->makePathRelative(
                $file->getPath(),
                $this->builder->getConfig()->getDataPath()
            );
            $subpath = trim($subpath, "./");
            $array = [];
            $path = $subpath ? $subpath . '/' . $basename : $basename;
            $this->pathToArray($array, $path, $dataArray);

            $dataArray = array_merge_recursive(
                $this->builder->getData(),
                $array
            );
            $this->builder->setData($dataArray);

            $message = sprintf('"%s" loaded', $path);
            call_user_func_array($this->builder->getMessageCb(), ['DATA_PROGRESS', $message, $count, $max]);
        }
    }

    private function pathToArray(&$arr, $path, $value, $separator = '/')
    {
        $keys = explode($separator, $path);

        foreach ($keys as $key) {
            $arr = &$arr[$key];
        }

        $arr = $value;
    }
}
