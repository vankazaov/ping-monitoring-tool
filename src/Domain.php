<?php

declare(strict_types=1);

namespace PingMonitoringTool;

class Domain
{
    private string $value;

    public function __construct(string $domain)
    {
        if (empty($domain)) {
            throw new \InvalidArgumentException('Empty domain.');
        }

        if (filter_var($domain, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME) === false) {
            throw new \InvalidArgumentException('Invalid domain '. $domain);
        }

        $this->value = $domain;
    }

    /**
     * @return string
     */
    public function getValue(): string
    {
        return $this->value;
    }
}