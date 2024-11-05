<?php

namespace App\Service;

use App\Api\PostApi;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

/**
 * Сервис для получения постов.
 */
readonly class PostFetcherService
{
    /**
     * @param LoggerInterface $logger Логгер.
     * @param PostApi $postApi API для получения данных постов.
     * @param PostService $postService Сервис для обработки данных постов.
     * @param StateService $stateService Сервис для управления состоянием загрузки постов.
     */
    public function __construct(
        private LoggerInterface $logger,
        private PostApi $postApi,
        private PostService $postService,
        private FailedRequestService $failedRequestService,
        private StateService $stateService
    ) {
    }

    /**
     * Получает и обрабатывает посты с использованием API.
     *
     * @param int $limit Максимальное количество страниц для обработки.
     * @return void
     */
    public function fetchAndProcessPosts(int $limit): void
    {
        $page = $this->stateService->loadState('current_page', 1);

        while ($limit > 0) {
            $limit--;
            $currentPage = $page;
            $posts = $this->postApi->fetchPosts($currentPage);
            if (empty($posts)) {
                $this->logger->info("Все посты обработаны до страницы $currentPage.");
                break;
            }
            $page++;

            try {
                $fullPosts = $this->postApi->fetchFullPosts($posts)->getPoolPosts();
                $this->postService->processPosts($fullPosts);
                $this->stateService->saveState('current_page', $currentPage);
            } catch (RuntimeException $e) {
                $fullPosts = $this->postApi->getPoolPosts();
                $this->failedRequestService->handleFailedRequests($posts, $fullPosts, $currentPage);
                $this->postService->processPosts($fullPosts);

                $this->logger->error("Ошибка при получении постов: " . $e->getMessage());
                continue;
            }
        }
    }
}