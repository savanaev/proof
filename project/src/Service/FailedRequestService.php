<?php

namespace App\Service;

use App\Api\PostApi;
use App\Entity\FailedRequest;
use App\Repository\FailedRequestRepository;
use DateTimeImmutable;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

/**
 * Сервис для обработки неудачных запросов
 */
readonly class FailedRequestService
{
    /**
     * Конструктор FailedRequestService.
     *
     * @param LoggerInterface $logger Логгер.
     * @param PostApi $postApi API для получения постов.
     * @param FailedRequestRepository $repository Репозиторий для работы с неудачными запросами
     * @param PostService $postService Сервис для обработки постов
     */
    public function __construct(
        private LoggerInterface  $logger,
        private PostApi $postApi,
        private FailedRequestRepository $repository,
        private PostService $postService
    )
    {
    }

    /**
     * Получить и обработать неудачные запросы.
     *
     * @param int $limit Количество записей для получения
     * @return void
     */
    public function fetchAndProcessPosts(int $limit): void
    {
        $failedRequests = $this->getFailedRequestsForRetry($limit);
        foreach ($failedRequests as $failedRequest) {
            $postId = $failedRequest->getExternalId();
            $page = $failedRequest->getPage();
            $this->fetchAndProcessPost($postId, $page);
            $this->removeFailedRequest($failedRequest);
        }
    }

    /**
     * Получить и обработать неудачный запрос.
     *
     * @param string $postId Идентификатор поста
     * @param int $page Номер страницы
     * @return void
     * @throws ClientExceptionInterface
     * @throws RedirectionExceptionInterface
     * @throws ServerExceptionInterface
     * @throws TransportExceptionInterface
     */
    public function fetchAndProcessPost(string $postId, int $page): void
    {
        try {
            $post = $this->postApi->fetchPostByExternalId($postId);
            if ($post) {
                $this->postService->processPost($post);
                $this->logger->info("Пост с ID $postId перезагружен.");
            } else {
                $this->logger->error("Пост с ID $postId не найден.");
            }
        } catch (RuntimeException $e) {
            $this->saveFailedRequest($postId, $page);
            $this->logger->error("Ошибка при получении поста: " . $e->getMessage());
        }
    }

    /**
     * Получить список всех неудачных запросов для повторной обработки.
     *
     * @param int $limit Количество записей для получения
     * @return FailedRequest[]
     */
    public function getFailedRequestsForRetry(int $limit): array
    {
        return $this->repository->findBy([], ['createdAt' => 'ASC'], $limit);
    }

    /**
     * Удалить запись о неудачном запросе.
     *
     * @param FailedRequest $failedRequest Запись о неудачном запросе для удаления
     */
    public function removeFailedRequest(FailedRequest $failedRequest): void
    {
        $this->repository->remove($failedRequest, true);
    }

    /**
     * Сохранить неудачный запрос.
     *
     * @param string $externalId Идентификатор поста
     * @param int $page Номер страницы
     * @return void
     */
    public function saveFailedRequest(string $externalId, int $page): void
    {
        $failedRequest = $this->createFailedRequest($externalId, $page);
        $this->repository->save($failedRequest, true);
    }

    /**
     * Обработать неудачные запросы.
     *
     * @param array $posts Список постов.
     * @param array $fullPosts Список полных данных постов.
     * @param int $page Номер страницы.
     * @return void
     */
    public function handleFailedRequests(array $posts, array $fullPosts, int $page): void
    {
        $failedPosts = $this->getFailedPosts($posts, $fullPosts);
        if (!empty($failedPosts)) {
            foreach ($failedPosts as $post) {
                $this->saveFailedRequest($post['id'], $page);
            }
        }

    }

    /**
     * Фильтрация неудачных постов.
     *
     * @param array $posts Список постов.
     * @param array $fullPosts Список полных данных постов.
     * @return array Список неудачных постов.
     */
    private function getFailedPosts(array $posts, array $fullPosts): array
    {
        $fullPostIds = array_column($fullPosts, 'id');

        return array_filter($posts, fn($post) => !in_array($post['id'], $fullPostIds, true));
    }

    /**
     * Создать запись о неудачном запросе.
     *
     * @param string $externalId Идентификатор поста
     * @param int $page Номер страницы
     * @return FailedRequest
     */
    private function createFailedRequest(string $externalId, int $page): FailedRequest
    {
        $failedRequest = new FailedRequest();
        $failedRequest->setExternalId($externalId);
        $failedRequest->setPage($page);
        $failedRequest->setCreatedAt(new DateTimeImmutable());

        return $failedRequest;
    }
}