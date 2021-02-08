<?php

namespace Tracing\Zipkin\Span;

use Application\Exception\NotImplementedException;
use Laminas\Db\Adapter\Driver\Mysqli\Statement;
use Laminas\Db\Sql\AbstractPreparableSql;
use Laminas\Db\Sql\PreparableSqlInterface;
use Laminas\Db\Sql\TableIdentifier;
use ReflectionClass;
use Zipkin\Endpoint;
use Zipkin\Span;

use const Zipkin\Kind\CLIENT;

class DatabaseSpan extends SpanProxy
{
    protected const SERVICE = 'mysql';

    private $statement;
    private $config;

    /**
     * @param Span $span actual span
     * @param PreparableSqlInterface|string $statement
     * @param array $config
     */
    public function __construct(Span $span, $statement, array $config)
    {
        parent::__construct($span);
        $this->statement = $statement;
        $this->config = $config;
    }

    protected function init(): void
    {
        if (is_string($this->statement)) {
            $statementType = $this->guessTypeFromStr($this->statement);
            $mainTableName = $this->guessMainTableFromStr($this->statement);
        } elseif ($this->statement instanceof Statement) {
            $statementType = $this->guessTypeFromStr($this->statement->getSql());
            $mainTableName = $this->guessMainTableFromStr($this->statement->getSql());
        } elseif ($this->statement instanceof AbstractPreparableSql) {
            $statementType = strtolower((new ReflectionClass($this->statement))->getShortName());
            $mainTableName = $this->getMainTableFromStatementClass();
        } else {
            throw new NotImplementedException('Unsupported statement');
        }

        $host = $this->config['hostname'];
        $port = $this->config['port'];

        $this->span->setName($statementType);
        $this->span->tag('table', $mainTableName);
        $this->span->setKind(CLIENT);
        $this->span->setRemoteEndpoint(
            Endpoint::create(static::SERVICE, $this->getIpV4($host), $this->getIpV6($host), $port)
        );
    }

    private function guessMainTableFromStr(string $statement): string
    {
        $quotedTableNamePattern = '/.*?(?:update|from)\s*?(?:([^\s]+)\.)["`]+?([^"`]+)/i';
        $tableNamePattern = '/.*?(?:update|from)\s+?(?:([^\s]+)\.)([^\s]+)/i';
        $defaultSchema = $this->config['database'];
        $matches = [];
        if (
            preg_match($quotedTableNamePattern, $statement, $matches)
            || preg_match($tableNamePattern, $statement, $matches)
        ) {
            return count($matches) === 3 ? $matches[1] . '.' . $matches[2] : $defaultSchema . '.' . $matches[1];
        } else {
            return 'unknown';
        }
    }

    private function guessTypeFromStr(string $statement): string
    {
        $matches = [];
        if (preg_match('/.*?(select|update|delete)/i', $statement, $matches)) {
            return strtolower($matches[1]);
        } else {
            return 'unknown';
        }
    }

    private function getMainTableFromStatementClass(): string
    {
        $defaultSchema = $this->config['database'];
        $table = $this->statement->getRawState('table');
        if (is_string($table)) {
            return strpos($table, '.') === false ? "$defaultSchema.$table" : $table;
        } elseif (is_array($table)) {
            $mainTable = array_values($table)[0];
            return sprintf("%s.%s", $mainTable->getSchema() ?: $defaultSchema, $mainTable->getTable());
        } elseif ($table instanceof TableIdentifier) {
            return sprintf("%s.%s", $table->getSchema() ?: $defaultSchema, $table->getTable());
        } else {
            return 'unknown';
        }
    }
}