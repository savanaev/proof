<?php

namespace App\Api;

use App\HttpClient\AppHttpClientInterface;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

/**
 * Класс для работы с API.
 */
class PostApi
{
    /**
     * @var string URL API.
     */
    private const ENDPOINT = 'https://proof.moneymediagroup.co.uk/api';

    /**
     * @var int Максимальное количество попыток запроса.
     */
    private const MAX_RETRIES = 5;

    /**
     * @var int Задержка между попытками запроса.
     */
    private const RETRY_DELAY = 1;

    /**
     * @var array<int, array<string, mixed>> Список постов, полученных из API.
     */
    private array $poolPosts = [];

    /**
     * Конструктор класса PostApi.
     *
     * @param LoggerInterface $logger Логгер для логирования сообщений об ошибках и предупреждениях.
     * @param AppHttpClientInterface $httpClient HTTP-клиент для выполнения запросов.
     */
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly AppHttpClientInterface $httpClient
    ) {
    }

    /**
     * Получает список постов.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getPoolPosts(): array
    {
        return $this->poolPosts;
    }

    /**
     * Добавляет пост в пул.
     *
     * @param array<string, mixed> $post Данные поста для добавления в пул.
     * @return void
     */
    private function pollPost(array $post): void
    {
        $this->poolPosts[] = $post;
    }

    /**
     * Сбрасывает пул постов.
     *
     * @return void
     */
    public function resetPoolPosts(): void
    {
        $this->poolPosts = [];
    }

    /**
     * Получает список постов с указанной страницы.
     *
     * @param int $page Номер страницы для получения постов.
     * @return array<int, array<string, mixed>>
     * @throws TransportExceptionInterface При ошибке в транспортном уровне.
     */
    public function fetchPosts(int $page): array
    {
        return $this->sendRequestWithRetries(function () use ($page) {
            $url = self::ENDPOINT . '/posts';

            return $this->httpClient->request('GET', $url, [
                'query' => ['page' => $page],
            ]);
        });
    }

    /**
     * Получает полные данные для каждого поста из переданного списка.
     *
     * @param array<int, array<string, mixed>> $posts Список сокращенных данных постов.
     * @return static
     * @throws TransportExceptionInterface При ошибке в транспортном уровне.
     * @throws ClientExceptionInterface При ошибке клиента.
     * @throws ServerExceptionInterface При ошибке сервера.
     * @throws RedirectionExceptionInterface При ошибке редиректа.
     * @throws DecodingExceptionInterface При ошибке декодирования JSON.
     * @throws RuntimeException При превышении максимального количества попыток запроса.
     */
    public function fetchFullPosts(array $posts): static
    {
        $this->resetPoolPosts();

        $promises = [];
        foreach ($posts as $post) {
            $promises[] = $this->httpClient->request('GET', self::ENDPOINT . "/post/{$post['id']}");
        }

        $stream = $this->httpClient->stream($promises);
        foreach ($stream as $response => $chunkStream) {
            try {
                if ($chunkStream->isLast()) {
                    $fullPost = $response->toArray();
                    $this->pollPost($fullPost);
                }
            } catch (RuntimeException $e) {
                $this->logger->error("Не удалось получить пост: " . $e->getMessage());
                continue;
            }
        }

        return $this;
    }

    /**
     * Получает полные данные поста по его внешнему идентификатору.
     *
     * @param string $id Внешний идентификатор поста.
     * @return array<int, array<string, mixed>>
     * @throws RuntimeException При превышении максимального количества попыток запроса.
     * @throws TransportExceptionInterface При ошибке в транспортном уровне.
     * @throws ClientExceptionInterface При ошибке клиента.
     * @throws ServerExceptionInterface При ошибке сервера.
     * @throws RedirectionExceptionInterface При ошибке редиректа.
     */
    public function fetchPostByExternalId(string $id): array
    {
        return $this->sendRequestWithRetries(function () use ($id) {
            $url = self::ENDPOINT . "/post/$id";

            return $this->httpClient->request('GET', $url);
        });
    }

    /**
     * Выполняет запрос с повторными попытками при возникновении исключений транспортного уровня.
     *
     * @param callable $request Функция, выполняющая HTTP-запрос.
     * @return array<string, mixed>|array
     * @throws RuntimeException|TransportExceptionInterface Если превышено максимальное количество попыток.
     */
    private function sendRequestWithRetries(callable $request): array
    {
        $attempt = 0;
        $delay = self::RETRY_DELAY;

        while ($attempt < self::MAX_RETRIES) {
            try {
                $response = $request();

                $statusCode = $response->getStatusCode();
                $headers = $response->getHeaders();
                $this->waitForRateLimitReset($statusCode, $headers);

                $attempt = 0;

                return $response->toArray();
            } catch (TransportExceptionInterface $e) {
                $attempt++;
                $this->logger->warning("Произошла неудачная попытка номер - $attempt. " . $e->getMessage());

                if ($attempt >= self::MAX_RETRIES) {
                    throw new RuntimeException('Превышено максимальное количество попыток запроса.', 0, $e);
                }

                usleep($delay * 1000000);
                $delay *= 2;
            }
        }

        return [];
    }

    /**
     * Проверяет заголовки ответа на наличие ограничений по количеству запросов
     * и при необходимости ждет сброса лимита.
     *
     * @param int $statusCode Код состояния ответа.
     * @param array<string, mixed> $headers Заголовки ответа.
     * @return void
     */
    private function waitForRateLimitReset(int $statusCode, array $headers): void
    {
        if ($statusCode === 429 || (isset($headers['x-ratelimit-remaining']) && (int)$headers['x-ratelimit-remaining'][0] <= 0)) {
            $waitTime = 60;
            if (isset($headers['retry-after'])) {
                $waitTime = (int)$headers['retry-after'][0];
            }

            $this->logger->warning("Лимит запросов исчерпан. Ожидание до сброса $waitTime секунд...");
            sleep($waitTime);
        }
    }
}