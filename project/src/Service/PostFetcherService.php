<?php

namespace App\Service;

use App\Api\PostApi;
use App\Message\PostMessage;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\MessageBusInterface;

/**
 * Сервис для получения постов.
 */
readonly class PostFetcherService
{
    /**
     * @param LoggerInterface $logger Логгер.
     * @param PostApi $postApi API для получения данных постов.
     * @param StateService $stateService Сервис для управления состоянием загрузки постов.
     * @param MessageBusInterface $bus
     */
    public function __construct(
        private LoggerInterface $logger,
        private PostApi $postApi,
        private StateService $stateService,
        private MessageBusInterface $bus
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

            $this->bus->dispatch(new PostMessage($posts, $currentPage));

            $this->stateService->saveState('current_page', $currentPage);
        }
    }
}