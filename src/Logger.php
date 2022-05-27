<?php

declare(strict_types=1);

namespace PingMonitoringTool;

class Logger
{
    private $mode = 'prod';
    private $level = 0;
    private $repository;

    public function __construct($mode = 'prod', $level = 1, ?Repository $repository = null)
    {
        $this->level = $level;
        $this->mode = $mode;
        $this->repository = $repository;
    }

    public function error(string $message, array $trace): void
    {
        if ($this->mode === 'debug' && $this->level > 0) {
            echo $message . PHP_EOL;
        }
        $this->writeLogInDb('error', $message);
    }

    public function info(string $message): void
    {
        if ($this->mode === 'debug' && $this->level > 1) {
            echo date('Y-m-d H:i:s') . ': ' . $message . PHP_EOL;
            $this->writeLogInDb('info', $message);
        }
    }

    protected function writeLogInDb(string $type, string $message)
    {
        if (!is_null($this->repository) && $this->level > 0) {
            $this->repository->writeLog($type, $message);
        }
    }
}