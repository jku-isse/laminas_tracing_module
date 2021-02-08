<?php

namespace Tracing\Zipkin\Tracer;

use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Exception\ServiceNotCreatedException;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Laminas\ServiceManager\ServiceManager;

class Factory implements FactoryInterface
{
    /**
     * @inheritDoc
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        if (!($container instanceof ServiceManager)) {
            throw new ServiceNotCreatedException(
                '$container must be an instance of ' . ServiceManager::class . '.'
            );
        }

        $config = $container->get('configuration')['tracing']['zipkin'];
        return new Tracer($container, $config['httpReporterURL'], $config['localServiceName']);
    }
}
