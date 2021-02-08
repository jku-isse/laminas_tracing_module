<?php

namespace Tracing\Zipkin\Span;

use Laminas\Stdlib\RequestInterface;
use Zipkin\Propagation\TraceContext;
use Zipkin\Span;

use const Zipkin\Kind\SERVER;

/**
 * Proxy object for an API request span.
 */
class RequestSpan extends SpanProxy
{
    /** @var RequestInterface */
    private $request;

    public function __construct(Span $span, RequestInterface $request)
    {
        parent::__construct($span);
        $this->request = $request;
    }

    public function getContext(): TraceContext
    {
        return $this->span->getContext();
    }

    protected function init(): void
    {
        $this->span->setKind(SERVER);
        $this->span->tag('http.path', $this->request->getUri()->getPath());

        $this->span->setName($this->request->getMethod());
    }
}