<?php

namespace Tracing\Zipkin\Span;

use Zipkin\Endpoint;
use Zipkin\Span;

use const Zipkin\Kind\CLIENT;

class StorageSpan extends SpanProxy
{
    protected const SERVICE = 'S3';

    private $host;
    private $name;
    private $tags;

    public function __construct(Span $span, string $host, string $name, array $tags)
    {
        parent::__construct($span);

        $this->host = $host;
        $this->name = $name;
        $this->tags = $tags;
    }

    protected function init(): void
    {
        $this->span->setName($this->name);

        foreach ($this->tags as $tag => $value) {
            if (is_string($value)) {
                $this->span->tag($tag, $value);
            }
        }

        $this->span->setKind(CLIENT);

        $host = $this->host;
        $port = 80;
        $this->span->setRemoteEndpoint(
            Endpoint::create(static::SERVICE, $this->getIpV4($host), $this->getIpV6($host), $port)
        );
    }
}
