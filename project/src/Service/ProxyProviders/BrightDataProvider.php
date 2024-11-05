<?php

namespace App\Service\ProxyProviders;

/**
 * BrightData Provider
 * Прокси сервис brightdata.com
 */
class BrightDataProvider implements ProxyProviderInterface
{
    /**
     * Получает список прокси.
     *
     * @return array[]
     */
    public function getProxies(): array
    {
        $proxy = $this->proxy();

        return [
            [$proxy]
        ];
    }

    /**
     * Получает опции прокси.
     *
     * @return array[]
     */
    public function getProxyOptions(): array
    {
        $proxy = $this->proxy();

        return [
            ['proxy' => $proxy,],
        ];
    }

    /**
     * Прокси сервер.
     *
     * @return string
     */
    private function proxy(): string
    {
        $username = $_ENV['USERNAME'];
        $password = $_ENV['PASSWORD'];
        $host = $_ENV['PROXY_HOST'];

        return "http://$username:$password@$host";
    }

}