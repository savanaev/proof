<?php

namespace App\Repository;

use App\Entity\Post;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Exception;
use RuntimeException;

/**
 * @extends ServiceEntityRepository<Post>
 */
class PostRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Post::class);
    }

    public function save(Post $post, bool $flush = false): void
    {
        $this->getEntityManager()->persist($post);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * @param array<int, Post> $posts
     * @param callable $createPostEntity
     * @return bool
     */
    public function savePostsTransactionally(array $posts, callable $createPostEntity): bool
    {
        $this->getEntityManager()->beginTransaction();

        try {
            foreach ($posts as $post) {
                $existingPost = $this->findOneBy(['externalId' => $post['id']]);
                if (!$existingPost) {
                    $postEntity = $createPostEntity($post);
                    $this->getEntityManager()->persist($postEntity);
                }
            }

            $this->getEntityManager()->flush();
            $this->getEntityManager()->commit();

            return true;
        } catch (Exception $e) {
            $this->getEntityManager()->rollback();
            throw new RuntimeException('Ошибка при сохранении постов: ' . $e->getMessage());
        }
    }
}
