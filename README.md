# Laminas Tracing Module

A [Laminas](https://getlaminas.org/) module to trace HTTP-requests to a Laminas application and submit the traces to a
[Zipkin](https://zipkin.io/) backend. Provides support for tracing access to databases supported by laminas-db, cache 
and Amazon S3 file storage. Also, a JavaScript helper to instrument fetch calls client side is included.
The traces contain tags that are used by [Trace Service Map](https://github.com/noah-kogler/trace_network_map) to generate 
a descriptive service map.

## Usage

Install the [dependencies](#dependencies), include the Tracing folder into the module folder of your Laminas application and 
register the module like described in the 
[Laminas documentation](https://docs.laminas.dev/tutorials/getting-started/modules/#informing-the-application-about-our-new-module).
Then you can use the following tools for tracing:

### HTTP requests

The tracing of incoming requests works out of the box when this module is registered. The configuration of this module
replaces the default application factory by a custom one, which injects an instrumented application class that takes care
of tracing.

### Database access

You have to do a manual instrumentation of the database access. <code>Tracing\Zipkin\Span\DatabaseSpan</code> 
helps you to extract the needed information from <code>$statement</code> which can be a laminas-db statement instance 
or just a plain string.

Example how to trace database access:

```php
use Tracing\Zipkin\Span\DatabaseSpan;
use Tracing\Zipkin\Tracer\Tracer;

$config = [
    'hostname' => 'DB HOST',
    'port' => 'DB PORT',
    'database' => 'NAME OF THE DEFAULT DATABASE',
];
$tracer = $serviceManager->get(Tracer::class);

$span = $tracer->startSpan(DatabaseSpan::class, ['statement' => $statement, 'config' => $config]);
// Execute the statement
$tracer->finishSpan($span, ['count' => 'NUMBER OF RESULTS']);
```

### Cache

Also tracing the cache access requires manual instrumentation. <code>Tracing\Zipkin\Span\CacheSpan</code> extracts the
required information.

Example how to trace cache access:

```php
use Tracing\Zipkin\Span\CacheSpan;
use Tracing\Zipkin\Tracer\Tracer;

$config = [
    'adapter' => [
        'options' => [
            'server' => [
                'host' => 'marvl-back.s3swwl.ng.0001.euw1.cache.amazonaws.com',
                'port' => 6379,
            ],
            'namespace' => 'live',
            'ttl' => 3600,
        ],
    ],
];
$tracer = $serviceManager->get(Tracer::class);
$span = $this->tracer->startSpan(CacheSpan::class, ['config' => $config, 'operation' => 'hSet', 'key' => 'test', 'value' => 'foo']);
// Perform the hSet operation
$this->tracer->finishSpan($this->openSpan, ['success' => true]);
```

### AWS S3

The S3 instrumentation for tracing [S3Client](https://docs.aws.amazon.com/aws-sdk-php/v3/api/class-Aws.S3.S3Client.html) access
works out of the box if this module is registered. But <code>Tracing\S3\S3Instrumentation</code> is not complete yet. 
There are some methods without instrumentation.

### JavaScript

Copy the <code>module/Tracing/js/trace.js</code> file to an appropriate place in your web client project.
It can be used to instrument the browser's fetch function and enrich the generated traces with tags needed for the
[Trace Service Map](https://github.com/noah-kogler/trace_network_map).

Example:
```javascript
import instrumented from '../utils/trace';

instrumented(global.fetch, 'https://example-api.url', options)
  .then(response => console.log(response));
```

## Dependencies

### PHP
* [openzipkin/zipkin](https://github.com/openzipkin/zipkin-php) version 2.0

### JavaScript

* [stacktrace-js](https://www.npmjs.com/package/stacktrace-js) version 2.0.2
* [zipkin](https://www.npmjs.com/package/zipkin) version 0.22.0
* [zipkin-context-cls](https://www.npmjs.com/package/zipkin-context-cls) version 0.22.0
* [zipkin-instrumentation-fetch](https://www.npmjs.com/package/zipkin-instrumentation-fetch) version 0.22.0
* [zipkin-transport-http](https://www.npmjs.com/package/zipkin-instrumentation-fetch) version 0.22.0

## Configuration

### PHP

Set the <code>httpReporterURL</code> in <code>config/module.config.php</code> to point to a Zipkin server.

### JavaScript

Set the <code>config.tracing.endpoint</code> in <code>js/trace.js</code> to point to a Zipkin server.