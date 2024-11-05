<?php

namespace App\HttpClient;

use Symfony\Contracts\HttpClient\ChunkInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;
use Symfony\Contracts\HttpClient\ResponseStreamInterface;

/**
 * Интерфейс для работы с Http клиентом
 */
interface AppHttpClientInterface
{
    /**
     * Отправка запроса
     *
     * @param string $method Метод запроса
     * @param string $url URL запроса
     * @param array<string, mixed> $options Опции запроса
     * @return ResponseInterface
     */
    public function request(string $method, string $url, array $options = []): ResponseInterface;

    /**
     * Отправка потокового запроса
     *
     * @param array<int, ResponseInterface> $responses Массив ответов
     * @param float|null $timeout Таймаут
     * @return ResponseStreamInterface
     */
    public function stream(array $responses, float $timeout = null): ResponseStreamInterface;
}