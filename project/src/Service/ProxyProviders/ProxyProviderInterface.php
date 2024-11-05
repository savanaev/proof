<?php

namespace App\Service\ProxyProviders;

/**
 * Интерфейс для получения прокси
 */
interface ProxyProviderInterface
{
    /**
     * Получает список прокси
     *
     * @return array
     */
    public function getProxies(): array;


    /**
     * Получает опции прокси
     *
     * @return array
     */
    public function getProxyOptions(): array;
}