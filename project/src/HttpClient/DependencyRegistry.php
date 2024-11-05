<?php

namespace App\HttpClient;

use InvalidArgumentException;

/**
 * Регистр зависимостей
 */
class DependencyRegistry
{
    /**
     * @var array<string, array<string, mixed>> Массив зависимостей
     */
    private array $dependencies = [];

    /**
     * Добавить зависимость
     *
     * @param string $type Тип клиента
     * @param string $className Класс клиента
     * @param array<string, mixed>$args Аргументы для создания клиента
     * @return void
     */
    public function add(string $type, string $className, array $args = []): void
    {
        $this->dependencies[$type] = ['class' => $className, 'args' => $args];
    }

    /**
     * Получить зависимости
     *
     * @param string $type Тип клиента
     * @return array<string, mixed> Зависимости
     * @throws InvalidArgumentException
     */
    public function get(string $type): array
    {
        if (!isset($this->dependencies[$type])) {
            throw new InvalidArgumentException("Неизвестный тип клиента: $type");
        }

        return $this->dependencies[$type];
    }
}