<?php
/*
 * Copyright (c) Arnaud Ligny <arnaud@ligny.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cecil\Step;

use Symfony\Component\Finder\Finder;
use Symfony\Component\Serializer\Encoder\CsvEncoder;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Encoder\YamlEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;

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

        $files = Finder::create()
            ->files()
            ->in($this->builder->getConfig()->getDataPath())
            ->name('/\.('.implode('|', $this->builder->getConfig()->get('data.ext')).')$/')
            ->sortByName(true);
        $max = count($files);

        if ($max <= 0) {
            $message = 'No files';
            call_user_func_array($this->builder->getMessageCb(), ['DATA_PROGRESS', $message]);

            return;
        }

        // YAML
        $serializerYaml = new Serializer([new ObjectNormalizer()], [new YamlEncoder()]);
        // JSON
        $serializerJson = new Serializer([new ObjectNormalizer()], [new JsonEncoder()]);
        // CSV
        $serializerCsv = new Serializer([new ObjectNormalizer()], [new CsvEncoder()]);
        // XML
        $serializerXml = new Serializer([new ObjectNormalizer()], [new XmlEncoder()]);

        $count = 0;

        /* @var $file \Symfony\Component\Finder\SplFileInfo */
        foreach ($files as $file) {
            $count++;
            set_error_handler(
                function ($severity, $message, $file, $line) {
                    throw new \ErrorException($message, 0, $severity, $file, $line, null);
                }
            );
            $data = $file->getContents();
            restore_error_handler();

            switch ($file->getExtension()) {
                case 'yml':
                case 'yaml':
                    $dataArray = $serializerYaml->decode($data, 'yaml');
                    break;
                case 'json':
                    $dataArray = $serializerJson->decode($data, 'json');
                    break;
                case 'csv':
                    $dataArray = $serializerCsv->decode($data, 'csv');
                    break;
                case 'xml':
                    $dataArray = $serializerXml->decode($data, 'xml');
                    break;
                default:
                    return;
            }

            $basename = $file->getBasename('.'.$file->getExtension());
            $subpath = \Cecil\Util::getFS()->makePathRelative(
                $file->getPath(),
                $this->builder->getConfig()->getDataPath()
            );
            $subpath = trim($subpath, './');
            $array = [];
            $path = $subpath ? $subpath.'/'.$basename : $basename;
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
