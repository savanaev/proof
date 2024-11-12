<?php

namespace App\Service;

use App\Entity\Post;
use App\Repository\PostRepository;
use DateTimeImmutable;
use Psr\Log\LoggerInterface;

/**
 * Сервис для работы с постами
 */
readonly class PostService
{
    /**
     * Конструктор PostService.
     *
     * @param PostRepository $postRepository Репозиторий постов.
     */
    public function __construct(private PostRepository $postRepository)
    {
    }

    /**
     * Обработка массива постов
     *
     * @param array $posts Массив постов
     * @return bool
     */
    public function processPosts(array $posts): bool
    {
        return $this->postRepository->savePostsTransactionally(
            $posts,
            fn($post) => $this->createPostEntity($post)
        );
    }

    /**
     * Создание сущности поста
     *
     * @param array $postData Данные поста
     * @return Post Сущность поста
     */
    private function createPostEntity(array $postData): Post
    {
        $post = new Post();
        $post->setExternalId($postData['id']);
        $post->setTitle($postData['title']);
        $post->setDescription($postData['description']);
        $post->setBody($postData['body']);
        $post->setCreatedAt(new DateTimeImmutable($postData['createdAt']));

        return $post;
    }
}