<?php

declare(strict_types=1);

namespace PingMonitoringTool;


class Monitoring
{
    private ErrorHandler $handler;
    private HttpClient $client;

    public function __construct(ErrorHandler $handler, HttpClient $client)
    {
        $this->handler = $handler;
        $this->client = $client;
    }

    public function ping(Domain $domain): ?Status
    {
        try {
            $result = $this->client->get($domain->getValue());
        } catch (\Exception $e) {
            $this->handler->handle($e);
        }

        if ($result['code'] > 0) {
            return new Status(
                $domain->getValue(),
                $result['datetime'],
                $result['code'],
                $result['code_definition'],
                $result['time'],
                $result['size']);
        }

        return null;
    }

    public function isOK(?Status $status): bool
    {
        if (is_null($status)) return false;

        if ($status->getCode() >= 200 && $status->getCode() < 300) return true;

        return false;
    }

}