<?php

namespace App\Service;

use App\Service\ProxyProviders\ProxyProviderInterface;
use Exception;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Symfony\Contracts\HttpClient\ResponseInterface;
use Symfony\Contracts\HttpClient\ResponseStreamInterface;

/**
 * Сервис по работе с прокси
 */
class ProxyRotatorService
{
    /**
     * @var array Массив опций прокси
     */
    private array $proxiesOptions = [];

    /**
     * @var int Количество сделанных запросов
     */
    private int $requestCount = 0;

    /**
     * @var int Индекс текущего прокси
     */
    private int $currentProxyIndex = 0;

    /**
     * Конструктор ProxyRotatorService
     *
     * @param HttpClientInterface $client Клиент для выполнения HTTP-запросов
     * @param ProxyProviderInterface $proxyProvider Провайдер прокси
     * @throws Exception
     */
    public function __construct(
        readonly private HttpClientInterface $client,
        private readonly ProxyProviderInterface $proxyProvider
    ) {
        $this->loadProxiesOptions();
    }

    /**
     * Отправляет HTTP-запрос с использованием текущего прокси
     *
     * @param string $method Метод HTTP-запроса
     * @param string $url URL-адрес
     * @param array $options Опции HTTP-запроса
     * @return ResponseInterface Ответ на HTTP-запрос
     *
     * @throws TransportExceptionInterface
     * @throws Exception
     */
    public function request(string $method, string $url, array $options = []): ResponseInterface
    {
        if ($this->requestCount >= $_ENV['FAILED_REQUESTS_LIMIT']) {
            $this->switchProxy();
            $this->requestCount = 0;
        }

        if (empty($this->proxiesOptions)) {
            $this->loadProxiesOptions();
        }

        $options['verify_peer'] = false;
        $options['verify_host'] = false;

        $proxiesOptions = $this->proxiesOptions[$this->currentProxyIndex];
        $options = array_merge($options, $proxiesOptions);
        $this->requestCount++;

        try {
            return $this->client->request($method, $url, $options);
        } catch (Exception $e) {
            $this->switchProxy();
            $this->requestCount = 0;
            throw new Exception($e->getMessage());
        }
    }

    /**
     * Отправляет потоковый HTTP-запрос с использованием текущего прокси
     *
     * @param array $responses Массив ответов
     * @param float|null $timeout Таймаут
     * @return ResponseStreamInterface
     */
    public function stream(array $responses, float $timeout = null): ResponseStreamInterface
    {
        return $this->client->stream($responses, $timeout);
    }

    /**
     * Переключает на следующий прокси
     *
     * @return void
     */
    private function switchProxy(): void
    {
        $this->currentProxyIndex = ($this->currentProxyIndex + 1) % count($this->proxiesOptions);
    }

    /**
     * Загружает прокси из провайдера
     *
     * @return void
     * @throws Exception
     */
    private function loadProxiesOptions(): void
    {
        $this->proxiesOptions = $this->proxyProvider->getProxyOptions();

        if (empty($this->proxiesOptions)) {
            throw new Exception('Не удалось загрузить прокси.');
        }
    }
}