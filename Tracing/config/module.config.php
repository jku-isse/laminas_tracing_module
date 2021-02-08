<?php

namespace Tracing;

use Aws\S3\S3Client;
use Tracing\Application\Factory as ApplicationFactory;
use Tracing\S3\Factory as S3Factory;
use Tracing\Zipkin\Span\CacheSpan as ZipkinCacheSpan;
use Tracing\Zipkin\Span\DatabaseSpan as ZipkinDatabaseSpan;
use Tracing\Zipkin\Span\Factory as ZipkinSpanFactory;
use Tracing\Zipkin\Span\RequestSpan as ZipkinRequestSpan;
use Tracing\Zipkin\Span\StorageSpan as ZipkinStorageSpan;
use Tracing\Zipkin\Tracer\Factory as ZipkinTracerFactory;
use Tracing\Zipkin\Tracer\Tracer as ZipkinTracer;

return [
    'tracing' => [
        'enabled' => true,
        'zipkin' => [
            'httpReporterURL' => 'http://127.0.0.1:9411/api/v2/spans',
            'localServiceName' => 'api',
        ],
    ],
    'service_manager' => [
        'factories' => [
            'Application' => ApplicationFactory::class,
            S3Client::class => S3Factory::class,
            ZipkinTracer::class => ZipkinTracerFactory::class,
            ZipkinRequestSpan::class => ZipkinSpanFactory::class,
            ZipkinDatabaseSpan::class => ZipkinSpanFactory::class,
            ZipkinCacheSpan::class => ZipkinSpanFactory::class,
            ZipkinStorageSpan::class => ZipkinSpanFactory::class,
        ],
    ],
];
