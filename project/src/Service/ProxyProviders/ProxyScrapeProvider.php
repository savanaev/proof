<?php

namespace App\Service\ProxyProviders;

use Exception;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * ProxyScrape Provider
 * Прокси сервис proxyscrape.com
 */
readonly class ProxyScrapeProvider implements ProxyProviderInterface
{
    private string $apiUrl;

    /**
     * Конструктор ProxyScrapeProvider
     *
     * @param LoggerInterface $logger Логгер
     * @param HttpClientInterface $client HTTP клиент
     */
    public function __construct(
        private LoggerInterface  $logger,
        private HttpClientInterface $client
    )
    {
        $this->apiUrl = sprintf('%s&key=%s', $_ENV['PROXY_API_URL'], $_ENV['API_KEY']);
    }

    /**
     * Получает список прокси.
     *
     * @return array[]
     * @throws ClientExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     */
    public function getProxies(): array
    {
        try {
            $response = $this->client->request('GET', $this->apiUrl);
            $data = $response->toArray();

            if (empty($data['proxiesOptions'])) {
                throw new Exception('Список прокси ProxyScrape пуст');
            }

            return array_column($data['proxiesOptions'], 'proxy');
        } catch (ClientExceptionInterface|DecodingExceptionInterface|
        RedirectionExceptionInterface|ServerExceptionInterface|
        TransportExceptionInterface $e) {
            $this->logger->error('Ошибка загрузки прокси из ProxyScrape: ' . $e->getMessage());
        } catch (Exception $e) {
            $this->logger->error('Ошибка: ' . $e->getMessage());
        }

        return [];
    }

    /**
     * Получает опции прокси.
     *
     * @return array[]
     *
     * @throws ServerExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ClientExceptionInterface
     */
    public function getProxyOptions(): array
    {
        return array_map(fn($proxy) => ['proxy' => $proxy], $this->getProxies());
    }
}