<?php

declare(strict_types=1);

namespace Podium\Api\Interfaces;

use yii\data\DataFilter;
use yii\data\Pagination;
use yii\data\Sort;

interface RepositoryInterface
{
    /**
     * @return int|string|array
     */
    public function getId();

    public function getParent(): RepositoryInterface;

    public function getAuthor(): MemberRepositoryInterface;

    public function getAllowedGroups(): array;

    /**
     * @param int|string|array $id
     */
    public function fetchOne($id): bool;

    /**
     * @param DataFilter|null            $filter
     * @param bool|array|Sort|null       $sort
     * @param bool|array|Pagination|null $pagination
     */
    public function fetchAll($filter = null, $sort = null, $pagination = null): void;

    public function getErrors(): array;

    public function delete(): bool;

    public function edit(array $data = []): bool;
}
