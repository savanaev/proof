<?php

namespace App\HttpClient;

use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Фабрика для создания нужного Http клиента
 */
readonly class AppHttpClientFactory
{
    /**
     * Конструктор AppHttpClientFactory
     *
     * @param DependencyRegistry $registry - регистр зависимостей
     */
    public function __construct(private DependencyRegistry $registry)
    {
    }

    /**
     * Создание Http клиента
     *
     * @param string $type тип Http клиента
     * @param HttpClientInterface $client основной Http клиент
     *
     * @return AppHttpClientInterface
     */
    public function create(string $type, HttpClientInterface $client): AppHttpClientInterface
    {
        $config = $this->registry->get($type);

        $className = $config['class'];
        $args = array_map(fn($arg) => $arg instanceof HttpClientInterface ? $client : $arg, $config['args']);

        return new $className(...$args);
    }
}