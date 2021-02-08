<?php

namespace Tracing\Zipkin\Span;

use RuntimeException;
use Zipkin\Span;

abstract class SpanProxy
{
    /** @var Span */
    protected $span;
    /** @var bool */
    private $open;

    public function __construct(Span $span)
    {
        $this->span = $span;
        $this->open = false;
    }

    public function isOpen(): bool
    {
        return $this->open;
    }

    public function start(): void
    {
        $this->init();
        $this->span->start();
        $this->open = true;
    }

    public function finish(array $resultTags = []): void
    {
        if (!$this->open) {
            throw new RuntimeException("Tried to finish a span that hasn't been started.");
        }

        foreach ($resultTags as $tag => $value) {
            $this->span->tag("result.$tag", $value);
        }

        $this->span->finish();
        $this->open = false;
    }

    abstract protected function init(): void;

    protected function getIpV4(string $hostname): ?string
    {
        $IPv4 = gethostbyname($hostname);
        return filter_var($IPv4, FILTER_VALIDATE_IP) ? $IPv4 : null;
    }

    /** @noinspection PhpUnusedParameterInspection */
    protected function getIpV6(string $hostname): ?string
    {
        return null; // our ipv6 support is as good as PHP's
    }
}
