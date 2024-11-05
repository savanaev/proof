<?php

namespace App\Service;

use App\Entity\ProcessState;
use App\Repository\ProcessStateRepository;
use DateTimeImmutable;

/**
 * Сервис для работы с состоянием процесса
 */
readonly class StateService
{

    /**
     * Конструктор StateService
     *
     * @param ProcessStateRepository $repository Репозиторий состояния процесса
     */
    public function __construct(private ProcessStateRepository $repository)
    {
    }

    /**
     * Сохранить состояние процесса
     *
     * @param string $key Ключ состояния
     * @param mixed $value Значение состояния
     * @return void
     */
    public function saveState(string $key, mixed $value): void
    {
        $state = $this->getStateEntity($key, $value);
        $this->repository->save($state, true);
    }

    /**
     * Загрузить состояние процесса
     *
     * @param string $key Ключ состояния
     * @param mixed|null $default Значение по умолчанию
     * @return mixed|string|null
     */
    public function loadState(string $key, mixed $default = null): mixed
    {
        $state = $this->repository->findOneBy(['keyName' => $key]);

        if (!$state) {
            return $default;
        }

        $decoded = json_decode($state->getValue(), true);

        return $decoded !== null ? $decoded : $state->getValue();
    }

    /**
     * Получить сущность состояния процесса
     *
     * @param string $key Ключ состояния
     * @param mixed $value Значение состояния
     * @return ProcessState
     */
    private function getStateEntity(string $key, mixed $value): ProcessState
    {
        $state = $this->repository->findOneBy(['keyName' => $key]);

        if (!$state) {
            $state = new ProcessState();
            $state->setKeyName($key);
        }

        $value = is_array($value) ? json_encode($value) : (string) $value;
        $state->setValue($value);
        $state->setUpdatedAt(new DateTimeImmutable());

        return $state;
    }
}