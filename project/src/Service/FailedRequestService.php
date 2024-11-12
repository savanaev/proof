<?php

namespace App\Service;

use App\Message\PostMessage;
use Symfony\Component\Messenger\MessageBusInterface;

/**
 * Сервис для обработки неудачных запросов
 */
readonly class FailedRequestService
{
    /**
     * Конструктор FailedRequestService.
     *
     * @param MessageBusInterface $bus Объект для отправки сообщений в очередь
     */
    public function __construct(private MessageBusInterface $bus)
    {
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
            $this->bus->dispatch(new PostMessage($failedPosts, $page));
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
        $failedPosts = array_filter($posts, fn($post) => !in_array($post['id'], $fullPostIds, true));

        return array_is_list($failedPosts) ? $failedPosts : array_values($failedPosts);
    }
}