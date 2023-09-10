<?php

declare(strict_types=1);

namespace PingMonitoringTool;

use InvalidArgumentException;

class Domain
{
    private $value;

    public function __construct(string $domain)
    {
        if (empty($domain)) {
            throw new InvalidArgumentException('Empty domain.');
        }

        if (filter_var($domain, FILTER_VALIDATE_DOMAIN, FILTER_VALIDATE_URL) === false) {
            throw new InvalidArgumentException('Invalid domain '. $domain);
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