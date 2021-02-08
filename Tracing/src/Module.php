<?php

namespace Tracing;

use Laminas\ModuleManager\Feature\ConfigProviderInterface;

/**
 * Class Module
 * @package Tracing
 * @codeCoverageIgnore
 */
class Module implements ConfigProviderInterface
{
    /** @inheritDoc */
    public function getConfig()
    {
        return include __DIR__ . '/../config/module.config.php';
    }
}
