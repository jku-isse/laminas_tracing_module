<?php

namespace Tracing\Zipkin\Span;

use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Exception\ServiceNotFoundException;
use Laminas\ServiceManager\Factory\FactoryInterface;

class Factory implements FactoryInterface
{
    /**
     * @inheritDoc
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        if ($requestedName === RequestSpan::class) {
            return new RequestSpan($options['span'], $options['request']);
        } elseif ($requestedName === DatabaseSpan::class) {
            return new DatabaseSpan($options['span'], $options['statement'], $options['config']);
        } elseif ($requestedName === StorageSpan::class) {
            return new StorageSpan($options['span'], $options['host'], $options['name'], $options['tags']);
        } elseif ($requestedName === CacheSpan::class) {
            return new CacheSpan(
                $options['span'],
                $options['config'],
                $options['operation'],
                $options['hash'],
                array_key_exists('key', $options) ? $options['key'] : null,
                array_key_exists('ttl', $options) ? $options['ttl'] : null
            );
        } else {
            throw new ServiceNotFoundException(
                "Requested span $requestedName not supported by " . get_class($this)
            );
        }
    }
}
