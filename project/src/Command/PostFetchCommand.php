<?php

namespace App\Command;

use App\Service\PostFetcherService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Command\LockableTrait;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;

/**
 * Команда для получения данных по API.
 */
#[AsCommand(
    name: 'app:fetch-posts',
    description: 'Получение данных по API',
)]
class PostFetchCommand extends Command
{
    use LockableTrait;

    /**
     * Конструктор команды.
     *
     * @param PostFetcherService $postFetcherService Сервис для получения постов.
     */
    public function __construct(readonly private PostFetcherService $postFetcherService)
    {
        parent::__construct();
    }

    /**
     * Настройка команды: добавление описания и опций.
     *
     * @return void
     */
    protected function configure(): void
    {
        $this
            ->setDescription('Получение данных')
            ->addOption(
                'limit',
                null,
                InputOption::VALUE_OPTIONAL,
                'Максимальное количество записей для обработки за 1 раз',
                $_ENV['REQUESTS_LIMIT']
            );
    }

    /**
     * Выполнение команды.
     *
     * @param InputInterface $input Входные данные команды.
     * @param OutputInterface $output Выходные данные команды.
     *
     * @return int Код успешного выполнения.
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        if (!$this->lock()) {
            $io->warning('Команда уже выполняется.');
            return Command::FAILURE;
        }

        $limit = (int) $input->getOption('limit');

        try {
            $this->postFetcherService->fetchAndProcessPosts($limit);
        } catch (
            ClientExceptionInterface|
            TransportExceptionInterface|
            ServerExceptionInterface|
            RedirectionExceptionInterface $e
        ) {
            $io->error($e->getMessage());

            return Command::FAILURE;
        }

        $io->success('Загрузка прошла успешно');

        $this->release();

        return Command::SUCCESS;
    }
}