<?php

namespace Tracing\Application;

use Interop\Container\ContainerInterface;
use Laminas\Mvc\Service\ApplicationFactory;
use Laminas\ServiceManager\Exception\ServiceNotCreatedException;
use Laminas\ServiceManager\ServiceManager;
use Tracing\Zipkin\Tracer\Tracer;

class Factory extends ApplicationFactory
{
    /**
     * @inheritDoc
     */
    public function __invoke(ContainerInterface $container, $name, array $options = null)
    {
        $config = $container->get('configuration')['tracing'];
        if ($config['enabled']) {
            if (!($container instanceof ServiceManager)) {
                throw new ServiceNotCreatedException(
                    '$container must be an instance of ' . ServiceManager::class . '.'
                );
            }
            return new InstrumentedApplication(
                $container->get(Tracer::class),
                $container,
                $container->get('EventManager'),
                $container->get('Request'),
                $container->get('Response')
            );
        } else {
            return parent::__invoke($container, $name, $options);
        }
    }
}
