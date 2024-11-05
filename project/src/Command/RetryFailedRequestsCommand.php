<?php

namespace App\Command;

use App\Service\FailedRequestService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Command\LockableTrait;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:retry-failed-requests',
    description: 'Повторное получение неудачных запросов по API.',
)]
class RetryFailedRequestsCommand extends Command
{
    use LockableTrait;

    public function __construct(readonly private FailedRequestService $failedRequestService)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Повторное получение данных неудачных запросов')
            ->addOption(
                'limit',
                null,
                InputOption::VALUE_OPTIONAL,
                'Максимальное количество записей для обработки за 1 раз',
                $_ENV['FAILED_REQUESTS_LIMIT']
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        if (!$this->lock()) {
            $io->warning('Команда уже выполняется.');
            return Command::FAILURE;
        }

        $limit = (int) $input->getOption('limit');
        $this->failedRequestService->fetchAndProcessPosts($limit);

        $io->success('Повторная загрузка прошла успешно');

        $this->release();

        return Command::SUCCESS;
    }
}
