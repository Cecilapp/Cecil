<?php declare(strict_types=1);

namespace Cecil\Assets\Image\Optimizers;

use Spatie\ImageOptimizer\Image;
use Spatie\ImageOptimizer\Optimizers\BaseOptimizer;

class Cwebp extends BaseOptimizer
{
    public $binaryName = 'cwebp';

    public function canHandle(Image $image): bool
    {
        return $image->mime() === 'image/webp';
    }

    public function getCommand(): string
    {
        $optionString = implode(' ', $this->options);

        return "\"{$this->binaryPath}{$this->binaryName}\" {$optionString}"
            .' '.escapeshellarg($this->imagePath)
            .' -o '.escapeshellarg($this->imagePath);
    }
}
