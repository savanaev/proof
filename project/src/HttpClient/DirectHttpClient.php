<?php

namespace App\HttpClient;

use Symfony\Contracts\HttpClient\ChunkInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;
use Symfony\Contracts\HttpClient\ResponseStreamInterface;

/**
 * Обертка Http для работы без использования сторонних сервисов
 */
readonly class DirectHttpClient implements AppHttpClientInterface
{

    /**
     * Конструктор DirectHttpClient
     *
     * @param HttpClientInterface $client Клиент для работы с Http
     */
    public function __construct(private HttpClientInterface $client)
    {
    }

    /**
     * Отправка запроса
     *
     * @param string $method Метод запроса
     * @param string $url URL запроса
     * @param array<string, mixed> $options Опции запроса
     * @return ResponseInterface
     * @throws TransportExceptionInterface
     */
    public function request(string $method, string $url, array $options = []): ResponseInterface
    {
        return $this->client->request($method, $url, $options);
    }

    /**
     * Отправка потокового запроса
     *
     * @param array<int, ResponseInterface> $responses Массив ответов
     * @param float|null $timeout Таймаут
     * @return ResponseStreamInterface
     */
    public function stream(array $responses, float $timeout = null): ResponseStreamInterface
    {
        return $this->client->stream($responses, $timeout);
    }
}