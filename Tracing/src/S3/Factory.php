<?php

namespace Tracing\S3;

use Aws\Credentials\Credentials;
use Aws\S3\S3Client;
use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;
use Tracing\Zipkin\Tracer\Tracer;

class Factory implements FactoryInterface
{
    /**
     * @inheritDoc
     */
    public function __invoke(ContainerInterface $container, $requestedName, array $options = null)
    {
        $config = $container->get('configuration');

        if (isset($options)) {
            $storageKey = 'storage'; // storage v1 is created with specific options
        } else {
            $storageKey = 'storage_v2';
            $options = [
                'region' => 'eu-west-1',
                'version' => 'latest',
                'credentials' => new Credentials(
                    $config['aws']['accessid'],
                    $config['aws']['accesskey']
                ),
            ];
        }

        $s3 = new S3Client($options);
        $url = $config['aws'][$storageKey]['cdn'] ?: $config['aws'][$storageKey]['url'];
        $host = str_replace(['https://', 'http://'], '', $url);

        return $config['tracing']['enabled']
            ? new S3Instrumentation($s3, $container->get(Tracer::class), $host)
            : $s3;
    }
}
