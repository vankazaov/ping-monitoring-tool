<?php

declare(strict_types=1);

namespace PingMonitoringTool;

class ErrorHandler
{
    public function __construct(private Logger $logger) {}

    public function handle(\Exception $exception): void
    {
        $this->logger->error($exception->getMessage(), [
            'exception' => $exception
        ]);
    }
}