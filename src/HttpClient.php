<?php

declare(strict_types=1);

namespace PingMonitoringTool;

class HttpClient
{
    private $http_codes = [
        0=>"DOWN",
        100=>"Continue",
        101=>"Switching Protocols",
        200=>"OK",
        201=>"Created",
        202=>"Accepted",
        203=>"Non-Authoritative Information",
        204=>"No Content",
        205=>"Reset Content",
        206=>"Partial Content",
        300=>"Multiple Choices",
        301=>"Moved Permanently",
        302=>"Found",
        303=>"See Other",
        304=>"Not Modified",
        305=>"Use Proxy",
        306=>"(Unused)",
        307=>"Temporary Redirect",
        400=>"Bad Request",
        401=>"Unauthorized",
        402=>"Payment Required",
        403=>"Forbidden",
        404=>"Not Found",
        405=>"Method Not Allowed",
        406=>"Not Acceptable",
        407=>"Proxy Authentication Required",
        408=>"Request Timeout",
        409=>"Conflict",
        410=>"Gone",
        411=>"Length Required",
        412=>"Precondition Failed",
        413=>"Request Entity Too Large",
        414=>"Request-URI Too Long",
        415=>"Unsupported Media Type",
        416=>"Requested Range Not Satisfiable",
        417=>"Expectation Failed",
        500=>"Internal Server Error",
        501=>"Not Implemented",
        502=>"Bad Gateway",
        503=>"Service Unavailable",
        504=>"Gateway Timeout",
        505=>"HTTP Version Not Supported"
    ];

    public function get(string $domain): array
    {
        $chPing = curl_init();
        curl_setopt($chPing, CURLOPT_URL, $domain);
        curl_setopt($chPing, CURLOPT_USERAGENT, 'Mozilla/5.0 (X11; Ubuntu; Linux x86_64; rv:99.0) Gecko/20100101 Firefox/99.0');
        curl_setopt($chPing, CURLOPT_HEADER, true);
        curl_setopt($chPing, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($chPing, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($chPing, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($chPing, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($chPing, CURLOPT_TIMEOUT, 10);
        curl_exec($chPing);
        $result_curl = curl_getinfo($chPing);
        $resultCode = curl_getinfo($chPing, CURLINFO_RESPONSE_CODE);
        $resultCodeDefinition = $this->http_codes[$resultCode];
        $resultTime = (float) curl_getinfo($chPing,   CURLINFO_TOTAL_TIME);
        $resultSize = (int) curl_getinfo($chPing,   CURLINFO_SIZE_DOWNLOAD);

        if (curl_errno($chPing)) {
            $error_msg = curl_error($chPing);
        }

        curl_close($chPing);

        if (isset($error_msg)) {
            throw new \RuntimeException($error_msg);
        }

        return [
            'datetime' => new \DateTimeImmutable(),
            'domain' => $domain,
            'code' => $resultCode,
            'code_definition' => $resultCodeDefinition,
            'time' => $resultTime,
            'size' => $resultSize,
        ];
    }
}