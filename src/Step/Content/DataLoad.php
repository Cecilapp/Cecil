<?php
/**
 * This file is part of the Cecil/Cecil package.
 *
 * Copyright (c) Arnaud Ligny <arnaud@ligny.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Cecil\Step\Content;

use Cecil\Step\AbstractStep;
use Cecil\Util;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Serializer\Encoder\CsvEncoder;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Encoder\YamlEncoder;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer;

/**
 * Loads data files.
 */
class DataLoad extends AbstractStep
{
    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return 'Loading data';
    }

    /**
     * {@inheritdoc}
     */
    public function init($options)
    {
        /** @var \Cecil\Builder $builder */
        /** @var \Cecil\Config $config */
        if (is_dir($this->builder->getConfig()->getDataPath()) && $this->config->get('data.load')) {
            $this->canProcess = true;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function process()
    {
        $files = Finder::create()
            ->files()
            ->in($this->builder->getConfig()->getDataPath())
            ->name('/\.('.implode('|', (array) $this->builder->getConfig()->get('data.ext')).')$/')
            ->sortByName(true);
        $max = count($files);

        if ($max <= 0) {
            $message = 'No files';
            $this->builder->getLogger()->info($message);

            return;
        }

        $serializerYaml = new Serializer([new ObjectNormalizer()], [new YamlEncoder()]);
        $serializerJson = new Serializer([new ObjectNormalizer()], [new JsonEncoder()]);
        $serializerCsv = new Serializer([new ObjectNormalizer()], [new CsvEncoder()]);
        $serializerXml = new Serializer([new ObjectNormalizer()], [new XmlEncoder()]);
        $count = 0;

        /** @var \Symfony\Component\Finder\SplFileInfo $file */
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
            $subpath = \Cecil\Util\File::getFS()->makePathRelative(
                $file->getPath(),
                $this->builder->getConfig()->getDataPath()
            );
            $subpath = trim($subpath, './');
            $array = [];
            $path = !empty($subpath) ? Util::joinFile($subpath, $basename) : $basename;
            $this->pathToArray($array, $path, $dataArray);

            $dataArray = array_merge_recursive(
                $this->builder->getData(),
                $array
            );
            $this->builder->setData($dataArray);

            $message = sprintf('%s.%s', Util::joinFile($path), $file->getExtension());
            $this->builder->getLogger()->info($message, ['progress' => [$count, $count]]);
        }
    }

    /**
     * Converts a path to an array.
     *
     * @param array  $arr       Target array
     * @param string $path      Source path
     * @param array  $value     Source values
     * @param string $separator Separator (ie: /)
     */
    private function pathToArray(array &$arr, string $path, array $value, string $separator = DIRECTORY_SEPARATOR): void
    {
        $keys = explode($separator, $path);

        foreach ($keys as $key) {
            $arr = &$arr[$key];
        }

        $arr = $value;
    }
}
