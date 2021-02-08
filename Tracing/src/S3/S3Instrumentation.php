<?php

/** @noinspection PhpUnhandledExceptionInspection */

/** @noinspection PhpMissingReturnTypeInspection */

namespace Tracing\S3;

use Aws\CommandInterface;
use Aws\S3\S3Client;
use Aws\S3\S3ClientInterface;
use Throwable;
use Tracing\Zipkin\Span\SpanProxy;
use Tracing\Zipkin\Span\StorageSpan;
use Tracing\Zipkin\Tracer\Tracer;

class S3Instrumentation implements S3ClientInterface
{
    private $s3;
    private $tracer;
    private $host;

    public function __construct(S3Client $s3, Tracer $tracer, string $host)
    {
        $this->s3 = $s3;
        $this->tracer = $tracer;
        $this->host = $host;
    }

    /**
     * @inheritDoc
     */
    public function getCommand($name, array $args = [])
    {
        return $this->s3->getCommand($name, $args);
    }

    /**
     * @inheritDoc
     */
    public function execute(CommandInterface $command)
    {
        $span = $this->startSpan(['method' => 'execute', 'name' => $command->getName(), 'command' => $command]);
        try {
            $result = $this->s3->execute($command);
        } catch (Throwable $t) {
            $this->tracer->finishSpan($span, ['error' => $t->getMessage()]);
            throw $t;
        }
        $this->tracer->finishSpan($span, ['count' => $result->count()]);
        return $result;
    }

    /**
     * @inheritDoc
     */
    public function executeAsync(CommandInterface $command)
    {
        $span = $this->startSpan(['method' => 'executeAsync', 'name' => $command->getName(), 'command' => $command]);
        return $this->s3->executeAsync($command)->then(
            function ($result) use ($span): void {
                $this->tracer->finishSpan($span, ['count' => $result->count()]);
            },
            function ($reason) use ($span): void {
                $this->tracer->finishSpan($span, ['error' => $reason]);
            }
        );
    }

    /**
     * @inheritDoc
     */
    public function getCredentials()
    {
        return $this->s3->getCredentials();
    }

    /**
     * @inheritDoc
     */
    public function getRegion()
    {
        return $this->s3->getRegion();
    }

    /**
     * @inheritDoc
     */
    public function getEndpoint()
    {
        return $this->s3->getEndpoint();
    }

    /**
     * @inheritDoc
     */
    public function getApi()
    {
        return $this->s3->getApi();
    }

    /**
     * @inheritDoc
     */
    public function getConfig($option = null)
    {
        return $this->s3->getConfig($option);
    }

    /**
     * @inheritDoc
     */
    public function getHandlerList()
    {
        return $this->s3->getHandlerList();
    }

    /**
     * @inheritDoc
     */
    public function getIterator($name, array $args = [])
    {
        $span = $this->startSpan(['method' => 'getIterator', 'name' => $name, 'args' => $args]);

        try {
            $iterator = $this->s3->getIterator($name, $args);
        } catch (Throwable $t) {
            $this->tracer->finishSpan($span, ['error' => $t->getMessage()]);
            throw $t;
        }
        $this->tracer->finishSpan($span, ['success' => true]);

        return $iterator;
    }

    /**
     * @inheritDoc
     */
    public function getPaginator($name, array $args = [])
    {
        // TODO instrument if needed
        return $this->s3->getPaginator($name, $args);
    }

    /**
     * @inheritDoc
     */
    public function waitUntil($name, array $args = [])
    {
        // TODO instrument if needed
        return $this->s3->waitUntil($name, $args);
    }

    /**
     * @inheritDoc
     */
    public function getWaiter($name, array $args = [])
    {
        // TODO instrument if needed
        return $this->s3->getWaiter($name, $args);
    }

    /**
     * @inheritDoc
     */
    public function createPresignedRequest(CommandInterface $command, $expires, array $options = [])
    {
        return $this->s3->createPresignedRequest($command, $expires, $options);
    }

    /**
     * @inheritDoc
     */
    public function getObjectUrl($bucket, $key)
    {
        return $this->s3->getObjectUrl($bucket, $key);
    }

    /**
     * @inheritDoc
     */
    public function doesBucketExist($bucket)
    {
        // TODO instrument if needed
        return $this->s3->doesBucketExist($bucket);
    }

    /**
     * @inheritDoc
     */
    public function doesObjectExist($bucket, $key, array $options = [])
    {
        $span = $this->startSpan(
            ['method' => 'doesObjectExist', 'bucket' => $bucket, 'key' => $key, 'options' => $options]
        );

        try {
            $result = $this->s3->doesObjectExist($bucket, $key, $options);
        } catch (Throwable $t) {
            $this->tracer->finishSpan($span, ['error' => $t->getMessage()]);
            throw $t;
        }
        $this->tracer->finishSpan($span, ['exists' => $result]);

        return $result;
    }

    /**
     * @inheritDoc
     */
    public function registerStreamWrapper()
    {
        $this->s3->registerStreamWrapper();
    }

    /**
     * @inheritDoc
     */
    public function deleteMatchingObjects($bucket, $prefix = '', $regex = '', array $options = [])
    {
        $span = $this->startSpan([
            'method' => 'deleteMatchingObjects',
            'bucket' => $bucket,
            'prefix' => $prefix,
            'regex' => $regex,
            'options' => $options
        ]);
        try {
            $this->s3->deleteMatchingObjects($bucket, $prefix, $regex, $options);
        } catch (Throwable $t) {
            $this->tracer->finishSpan($span, ['error' => $t->getMessage()]);
            throw $t;
        }
    }

    /**
     * @inheritDoc
     */
    public function deleteMatchingObjectsAsync($bucket, $prefix = '', $regex = '', array $options = [])
    {
        // TODO instrument if needed
        $this->s3->deleteMatchingObjectsAsync($bucket, $prefix, $regex, $options);
    }

    /**
     * @inheritDoc
     */
    public function upload($bucket, $key, $body, $acl = 'private', array $options = [])
    {
        // TODO instrument if needed
        return $this->s3->upload($bucket, $key, $body, $acl, $options);
    }

    /**
     * @inheritDoc
     */
    public function uploadAsync($bucket, $key, $body, $acl = 'private', array $options = [])
    {
        // TODO instrument if needed
        return $this->s3->uploadAsync($bucket, $key, $body, $acl, $options);
    }

    /**
     * @inheritDoc
     */
    public function copy($fromBucket, $fromKey, $destBucket, $destKey, $acl = 'private', array $options = [])
    {
        // TODO instrument if needed
        return $this->s3->copy($fromBucket, $fromKey, $destBucket, $destKey, $acl, $options);
    }

    /**
     * @inheritDoc
     */
    public function copyAsync($fromBucket, $fromKey, $destBucket, $destKey, $acl = 'private', array $options = [])
    {
        // TODO instrument if needed
        return $this->s3->copyAsync($fromBucket, $fromKey, $destBucket, $destKey, $acl, $options);
    }

    /**
     * @inheritDoc
     */
    public function uploadDirectory($directory, $bucket, $keyPrefix = null, array $options = [])
    {
        // TODO instrument if needed
        $this->s3->uploadDirectory($directory, $bucket, $keyPrefix, $options);
    }

    /**
     * @inheritDoc
     */
    public function uploadDirectoryAsync($directory, $bucket, $keyPrefix = null, array $options = [])
    {
        // TODO instrument if needed
        $this->s3->uploadDirectoryAsync($directory, $bucket, $keyPrefix, $options);
    }

    /**
     * @inheritDoc
     */
    public function downloadBucket($directory, $bucket, $keyPrefix = '', array $options = [])
    {
        // TODO instrument if needed
        $this->s3->downloadBucket($directory, $bucket, $keyPrefix, $options);
    }

    /**
     * @inheritDoc
     */
    public function downloadBucketAsync($directory, $bucket, $keyPrefix = '', array $options = [])
    {
        // TODO instrument if needed
        $this->s3->downloadBucketAsync($directory, $bucket, $keyPrefix, $options);
    }

    /**
     * @inheritDoc
     */
    public function determineBucketRegion($bucketName)
    {
        // TODO instrument if needed
        return $this->s3->determineBucketRegion($bucketName);
    }

    /**
     * @inheritDoc
     */
    public function determineBucketRegionAsync($bucketName)
    {
        // TODO instrument if needed
        return $this->s3->determineBucketRegionAsync($bucketName);
    }

    private function startSpan(array $options): SpanProxy
    {
        $tags = [];
        if (array_key_exists('name', $options)) {
            $tags['name'] = $options['name'];
        }
        if (array_key_exists('bucket', $options)) {
            $tags['bucket'] = $options['bucket'];
        }
        if (array_key_exists('key', $options)) {
            $tags['key'] = $options['key'];
        }
        if (array_key_exists('prefix', $options)) {
            $tags['prefix'] = $options['prefix'];
        }
        if (array_key_exists('regex', $options)) {
            $tags['regex'] = $options['regex'];
        }
        if (array_key_exists('command', $options)) {
            foreach ($options['command']->toArray() as $key => $value) {
                if (is_string($value)) {
                    $tags["command.$key"] = $value;
                }
            }
        }
        if (array_key_exists('args', $options)) {
            foreach ($options['args'] as $key => $value) {
                if (is_string($value)) {
                    $tags["args.$key"] = $value;
                }
            }
        }
        if (array_key_exists('options', $options)) {
            foreach ($options['options'] as $key => $value) {
                if (is_string($value)) {
                    $tags["options.$key"] = $value;
                }
            }
        }

        return $this->tracer->startSpan(
            StorageSpan::class,
            ['host' => $this->host, 'name' => $options['method'], 'tags' => $tags]
        );
    }

    /**
     * @inheritDoc
     */
    public function __call($name, array $arguments)
    {
        return $this->s3->__call($name, $arguments);
    }
}
