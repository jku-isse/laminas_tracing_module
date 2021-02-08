<?php

namespace Tracing\Zipkin\Span;

use Zipkin\Endpoint;
use Zipkin\Span;

use const Zipkin\Kind\CLIENT;

class CacheSpan extends SpanProxy
{
    protected const SERVICE = 'redis';

    private $config;
    private $operation;
    private $hash;
    private $key;
    private $ttl;

    public function __construct(
        Span $span,
        array $config,
        string $operation,
        string $hash,
        ?string $key = null,
        ?string $ttl = null
    ) {
        parent::__construct($span);
        $this->config = $config;
        $this->operation = $operation;
        $this->hash = $hash;
        $this->key = $key;
        $this->ttl = $ttl;
    }

    protected function init(): void
    {
        $host = $this->config['adapter']['options']['server'][0];
        $port = $this->config['adapter']['options']['server'][1];

        $this->span->setName($this->operation);
        $this->span->tag('hash', $this->hash);
        if (isset($key)) {
            $this->span->tag('key', $this->key);
        }
        if (isset($ttl)) {
            $this->span->tag('ttl', $this->ttl);
        }
        $this->span->setKind(CLIENT);

        $this->span->setRemoteEndpoint(
            Endpoint::create(static::SERVICE, $this->getIpV4($host), $this->getIpV6($host), $port)
        );
    }
}
