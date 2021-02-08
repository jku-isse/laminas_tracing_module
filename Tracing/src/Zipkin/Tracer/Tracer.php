<?php

namespace Tracing\Zipkin\Tracer;

use Laminas\ServiceManager\ServiceManager;
use Tracing\Zipkin\Span\RequestSpan;
use Laminas\Stdlib\RequestInterface;
use Tracing\Zipkin\Span\SpanProxy;
use Zipkin\Endpoint;
use Zipkin\Propagation\Map;
use Zipkin\Reporters\Http;
use Zipkin\Reporters\Http\CurlFactory;
use Zipkin\Samplers\BinarySampler;
use Zipkin\TracingBuilder;

class Tracer
{
    private $serviceManger;
    private $httpReporterURL;
    private $localServiceName;
    private $tracer;
    private $requestSpan;

    public function __construct(ServiceManager $serviceManager, string $httpReporterURL, string $localServiceName)
    {
        $this->serviceManger = $serviceManager;
        $this->httpReporterURL = $httpReporterURL;
        $this->localServiceName = $localServiceName;
    }

    /**
     * Starts the root span for a request to the api. All other spans will be children of this one.
     * @param RequestInterface $request request to the api backend
     */
    public function startRequestSpan(RequestInterface $request): void
    {
        $uri = $request->getUri();
        $localServicePort = $uri->getPort();
        $IPv4 = gethostbyname($uri->getHost() . '.');
        $localServiceIPv4 = filter_var($IPv4, FILTER_VALIDATE_IP) ? $IPv4 : null;
        $localServiceIPv6 = null; // our ipv6 support is as good as PHP's

        $endpoint = Endpoint::create($this->localServiceName, $localServiceIPv4, $localServiceIPv6, $localServicePort);

        $reporter = new Http(
            CurlFactory::create(),
            ['endpoint_url' => $this->httpReporterURL]
        );
        $sampler = BinarySampler::createAsAlwaysSample();

        $tracing = TracingBuilder::create()
            ->havingLocalEndpoint($endpoint)
            ->havingSampler($sampler)
            ->havingReporter($reporter)
            ->build();

        $this->tracer = $tracing->getTracer();

        $requestHeaders  = $request->getHeaders()->toArray();

        $extractor = $tracing->getPropagation()->getExtractor(new Map());
        $extractedContext = $extractor($requestHeaders);

        $this->requestSpan = $this->serviceManger->build(
            RequestSpan::class,
            ['span' => $this->tracer->nextSpan($extractedContext), 'request' => $request]
        );
        $this->requestSpan->start();
    }

    /**
     * Finishes the root span if it's set and started.
     */
    public function finishRequestSpan(): void
    {
        if (isset($this->requestSpan)) {
            $this->requestSpan->finish();
        }
        if (isset($this->tracer)) {
            $this->tracer->flush(); // finishes all spans that remained open
        }
    }

    /**
     * Starts a child span if the root request span is set.
     * @param string $className a span proxy class name for the type of the span
     * @param array $options associative array containing the constructor parameters for the span
     * @return SpanProxy|null the created and started span proxy or null in case of failure
     */
    public function startSpan(string $className, array $options): ?SpanProxy
    {
        if (!$this->requestSpan) {
            return null; // not tracing the request
        }

        $options['span'] = $this->tracer->newChild($this->requestSpan->getContext());
        $span = $this->serviceManger->build($className, $options);
        $span->start();

        return $span;
    }

    /**
     * Finishes a span if it's set and open.
     * @param SpanProxy|null $span potentially open span
     * @param array $resultTags optional associative array with information about results which will be added as tags
     */
    public function finishSpan(?SpanProxy $span, array $resultTags = []): void
    {
        if (isset($span) && $span->isOpen()) {
            $span->finish($resultTags);
        }
    }
}
