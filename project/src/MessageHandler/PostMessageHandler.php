<?php

namespace App\MessageHandler;

use App\Message\PostMessage;
use App\Api\PostApi;
use App\Service\PostService;
use App\Service\FailedRequestService;
use Psr\Log\LoggerInterface;
use RuntimeException;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;


#[AsMessageHandler]
readonly class PostMessageHandler
{
    /**
     * Конструктор PostMessageHandler.
     *
     * @param PostApi $postApi API для получения данных постов.
     * @param PostService $postService Сервис для обработки данных постов.
     * @param FailedRequestService $failedRequestService Сервис для обработки ошибок при получении данных постов.
     * @param LoggerInterface $logger Сервис для логирования ошибок.
     */
    public function __construct(
        private PostApi $postApi,
        private PostService $postService,
        private FailedRequestService $failedRequestService,
        private LoggerInterface $logger
    )
    {
    }

    /**
     * Обработчик сообщения PostMessage.
     *
     * @param PostMessage $message Сообщение с данными постов.
     * @return void
     */
    public function __invoke(PostMessage $message): void
    {
        $posts = $message->getPosts();
        $page = $message->getPage();

        try {
            $fullPosts = $this->postApi->fetchFullPosts($posts)->getPoolPosts();
            $this->postService->processPosts($fullPosts);
        } catch (RuntimeException $e) {
            $fullPosts = $this->postApi->getPoolPosts();
            $this->failedRequestService->handleFailedRequests($posts, $fullPosts, $page);
            $this->postService->processPosts($fullPosts);

            $this->logger->error("Ошибка при получении постов: " . $e->getMessage());
        }

    }
}