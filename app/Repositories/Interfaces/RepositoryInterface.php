<?php

namespace App\Repositories\Interfaces;

use Illuminate\Database\Eloquent\Model;

/**
 * Interface RepositoryInterface
 * Generic repository interface defining standard and advanced CRUD operations.
 * (Interface tổng quát định nghĩa các hoạt động CRUD cơ bản và nâng cao)
 */
interface RepositoryInterface
{
    /**
     * Get all records.
     *
     * @return mixed
     */
    public function all();

    /**
     * Create a new record.
     *
     * @return mixed
     */
    public function create(array $attributes = []);

    /**
     * Insert multiple records.
     *
     * @return mixed
     */
    public function insert(array $attributes);

    /**
     * Update a record by ID.
     *
     * @param  mixed  $id
     * @return mixed
     */
    public function update($id, array $attributes = []);

    /**
     * Delete a record by ID.
     *
     * @param  mixed  $id
     * @return mixed
     */
    public function delete($id);

    /**
     * Find a record by ID or throw exception.
     *
     * @param  mixed  $id
     * @return mixed
     */
    public function show($id);

    /**
     * Find a record by ID.
     *
     * @param  mixed  $id
     * @return mixed
     */
    public function find($id);

    /**
     * Find a record by ID with specific columns.
     *
     * @param  mixed  $id
     * @return mixed
     */
    public function findOnlyColumn($id, array $columns = ['*']);

    /**
     * Get the first record.
     *
     * @return mixed
     */
    public function first();

    /**
     * Eager load relations.
     *
     * @param  mixed  $relations
     * @return $this
     */
    public function with($relations);

    /**
     * Order by column.
     *
     * @return $this
     */
    public function orderBy(string $column, string $direction = 'asc');

    /**
     * Limit results.
     *
     * @return $this
     */
    public function limit(int $limit);

    /**
     * Get the query builder.
     *
     * @return mixed
     */
    public function getQuery();

    /**
     * Clear the query builder.
     *
     * @return mixed
     */
    public function clearQuery();

    /**
     * Find records by filter.
     *
     * @return mixed
     */
    public function findBy(array $filter, bool $toArray = true);

    /**
     * Find one record by filter.
     *
     * @return mixed
     */
    public function findOneBy(array $filter, bool $toArray = true);

    /**
     * Paginate results.
     *
     * @param  int  $page
     * @return mixed
     */
    public function paginate($page);

    /**
     * Update records by condition.
     */
    public function updateWhere(array $attributes = [], array $params = []): void;

    /**
     * Delete records by condition.
     */
    public function deleteBy(array $filter): void;

    /**
     * Find records where in.
     *
     * @return mixed
     */
    public function findWhereIn(array $filter, bool $toArray = true);

    /**
     * Delete records where in.
     */
    public function deleteWhereIn(array $filter): void;

    /**
     * Update or create a record.
     */
    public function updateOrCreate(array $attributes = [], array $params = []): void;

    /**
     * Count records.
     */
    public function countRecord(array $filter = []): int;

    /**
     * Find records by IDs.
     */
    public function findByIds(array $ids, array $filter = [], bool $returnOnlyIds = false): array;

    /**
     * Update where in.
     */
    public function updateWhereIn(string $column, array $values, array $attributes, array $whereConditions = []): void;

    /**
     * Update where not in.
     */
    public function updateWhereNotIn(string $column, array $values, array $attributes, array $whereConditions = []): void;

    /**
     * Delete not in IDs.
     */
    public function deleteNotInIds(string $columnName, int $value, array $ids, string $primaryKey = 'id'): void;

    /**
     * Sync many-to-many.
     *
     * @return mixed
     */
    public function sync(Model $model, string $relation, array $attributes, bool $detaching = true);

    /**
     * Attach many-to-many.
     *
     * @return mixed
     */
    public function attach(Model $model, string $relation, array $attributes);

    /**
     * Detach many-to-many.
     *
     * @return mixed
     */
    public function detach(Model $model, string $relation, array $attributes = []);

    /**
     * Generate a unique slug for the model.
     * (Tạo slug duy nhất cho model)
     */
    public function generateUniqueSlug(string $value, string $column = 'slug', ?int $ignoreId = null): string;
}
