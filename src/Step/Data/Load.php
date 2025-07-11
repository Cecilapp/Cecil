<?php

/**
 * This file is part of Cecil.
 *
 * (c) Arnaud Ligny <arnaud@ligny.fr>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Cecil\Step\Data;

use Cecil\Collection\Page\PrefixSuffix;
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
 * Load step class.
 *
 * This class is responsible for loading data files from a specified directory,
 * decoding their contents based on the file extension, and merging the data
 * into the builder's data collection. It supports various file formats such as
 * YAML, JSON, CSV, and XML. The loaded data is organized into a nested array
 * structure based on the file paths and language suffixes.
 */
class Load extends AbstractStep
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
    public function init(array $options): void
    {
        if (is_dir($this->config->getDataPath()) && $this->config->isEnabled('data.load')) {
            $this->canProcess = true;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function process(): void
    {
        $files = Finder::create()
            ->files()
            ->in($this->config->getDataPath())
            ->name('/\.(' . implode('|', (array) $this->config->get('data.ext')) . ')$/')
            ->sortByName(true);
        $total = \count($files);

        if ($total < 1) {
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
                    $dataAsArray = $serializerYaml->decode($data, 'yaml');
                    break;
                case 'json':
                    $dataAsArray = $serializerJson->decode($data, 'json');
                    break;
                case 'csv':
                    $dataAsArray = $serializerCsv->decode($data, 'csv');
                    break;
                case 'xml':
                    $dataAsArray = $serializerXml->decode($data, 'xml');
                    break;
                default:
                    return;
            }

            $lang = $this->config->getLanguageDefault();
            if (PrefixSuffix::hasSuffix($file->getBasename('.' . $file->getExtension()))) {
                $lang = PrefixSuffix::getSuffix($file->getBasename('.' . $file->getExtension()));
            }
            $basename = $file->getBasename('.' . $file->getExtension());
            $subpath = \Cecil\Util\File::getFS()->makePathRelative(
                $file->getPath(),
                $this->config->getDataPath()
            );
            $subpath = trim($subpath, './');
            $array = [];
            $path = !empty($subpath) ? Util::joinFile($subpath, $basename) : $basename;
            $localizedPath = Util::joinFile((string) $lang, PrefixSuffix::sub($path));
            $this->pathToArray($array, $localizedPath, $dataAsArray);

            $dataAsArray = array_merge_recursive(
                $this->builder->getData(),
                $array
            );
            $this->builder->setData($dataAsArray);

            $message = \sprintf('File "%s.%s" loaded', Util::joinFile($path), $file->getExtension());
            $this->builder->getLogger()->info($message, ['progress' => [$count, $total]]);
        }
    }

    /**
     * Puts a path/value couple into an array.
     *
     * @param array  $arr       Target array
     * @param string $path      Source path
     * @param array  $value     Source values
     * @param string $separator Path separator (ie: '/')
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
