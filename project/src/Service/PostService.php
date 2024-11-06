<?php

namespace App\Service;

use App\Entity\Post;
use App\Repository\PostRepository;
use DateTimeImmutable;
use Exception;
use Psr\Log\LoggerInterface;
use RuntimeException;

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
     * Обработка поста
     *
     * @param array $post Данные поста
     * @return void
     * @throws Exception
     */
    public function processPost(array $post)
    {
        $existingPost = $this->postRepository->findOneBy(['externalId' => $post['id']]);
        if (!$existingPost) {
            $postEntity = $this->createPostEntity($post);
            $this->postRepository->save($postEntity, true);
        }
    }

    /**
     * Создание сущности поста
     *
     * @param array $postData Данные поста
     * @return Post Сущность поста
     * @throws Exception
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