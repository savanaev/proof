<?php

namespace App\Message;

/**
 * Сообщение для передачи данных постов в очередь.
 */
readonly class PostMessage
{

    /**
     * Конструктор PostMessage
     *
     * @param array $posts Массив постов
     * @param int $page Номер страницы
     */
    public function __construct(private array $posts, private int $page)
    {
    }

    /**
     * Получить массив постов
     *
     * @return array Массив постов
     */
    public function getPosts(): array
    {
        return $this->posts;
    }

    /**
     * Получить номер страницы
     *
     * @return int Номер страницы
     */
    public function getPage(): int
    {
        return $this->page;
    }
}