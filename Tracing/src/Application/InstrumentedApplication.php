<?php

namespace Tracing\Application;

use Laminas\EventManager\EventManagerInterface;
use Laminas\Mvc\Application;
use Laminas\Mvc\MvcEvent;
use Laminas\ServiceManager\ServiceManager;
use Laminas\Stdlib\RequestInterface;
use Laminas\Stdlib\ResponseInterface;
use Tracing\Zipkin\Tracer\Tracer;

class InstrumentedApplication extends Application
{
    private $tracer;

    /** @inheritDoc */
    public function __construct(
        Tracer $tracer,
        ServiceManager $serviceManager,
        EventManagerInterface $events = null,
        RequestInterface $request = null,
        ResponseInterface $response = null
    ) {
        parent::__construct($serviceManager, $events, $request, $response);
        $this->tracer = $tracer;
    }

    /** @inheritDoc */
    public function run()
    {
        $this->tracer->startRequestSpan($this->request);
        return parent::run();
    }

    /** @inheritDoc */
    protected function completeRequest(MvcEvent $event)
    {
        $this->tracer->finishRequestSpan();
        return parent::completeRequest($event);
    }
}
