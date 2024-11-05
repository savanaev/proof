<?php

namespace App\HttpClient;

use App\Service\ProxyRotatorService;
use Symfony\Contracts\HttpClient\ChunkInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;
use Symfony\Contracts\HttpClient\ResponseStreamInterface;

/**
 * Обертка Http клиента для работы через прокси
 */
readonly class ProxyHttpClient implements AppHttpClientInterface
{
    /**
     * Конструктор ProxyHttpClient.
     *
     * @param ProxyRotatorService $proxyRotator Сервис по работе с прокси
     */
    public function __construct(private ProxyRotatorService $proxyRotator)
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
        return $this->proxyRotator->request($method, $url, $options);
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
        return $this->proxyRotator->stream($responses, $timeout);
    }
}