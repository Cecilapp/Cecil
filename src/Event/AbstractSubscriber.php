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

namespace Cecil\Event;

use Cecil\BuildContextInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Event subscriber abstract class.
 *
 * Subscribers declared in the `extensions.subscribers` configuration are instantiated with the build context.
 */
abstract class AbstractSubscriber implements EventSubscriberInterface
{
    /** @var BuildContextInterface */
    protected $builder;

    /** @var \Cecil\Config */
    protected $config;

    public function __construct(BuildContextInterface $builder)
    {
        $this->builder = $builder;
        $this->config = $builder->getConfig();
    }
}
