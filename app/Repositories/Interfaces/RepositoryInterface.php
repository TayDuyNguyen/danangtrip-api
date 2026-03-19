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
     * @return mixed
     */
    public function all();

    /**
     * Create a new record.
     * @param array $attributes
     * @return mixed
     */
    public function create(array $attributes = []);

    /**
     * Insert multiple records.
     * @param array $attributes
     * @return mixed
     */
    public function insert(array $attributes);

    /**
     * Update a record by ID.
     * @param mixed $id
     * @param array $attributes
     * @return mixed
     */
    public function update($id, array $attributes = []);

    /**
     * Delete a record by ID.
     * @param mixed $id
     * @return mixed
     */
    public function delete($id);

    /**
     * Find a record by ID or throw exception.
     * @param mixed $id
     * @return mixed
     */
    public function show($id);

    /**
     * Find a record by ID.
     * @param mixed $id
     * @return mixed
     */
    public function find($id);

    /**
     * Find a record by ID with specific columns.
     * @param mixed $id
     * @param array $columns
     * @return mixed
     */
    public function findOnlyColumn($id, array $columns = ['*']);

    /**
     * Get the first record.
     * @return mixed
     */
    public function first();

    /**
     * Eager load relations.
     * @param mixed $relations
     * @return $this
     */
    public function with($relations);

    /**
     * Order by column.
     * @param string $column
     * @param string $direction
     * @return $this
     */
    public function orderBy(string $column, string $direction = 'asc');

    /**
     * Limit results.
     * @param int $limit
     * @return $this
     */
    public function limit(int $limit);

    /**
     * Get the query builder.
     * @return mixed
     */
    public function getQuery();

    /**
     * Clear the query builder.
     * @return mixed
     */
    public function clearQuery();

    /**
     * Find records by filter.
     * @param array $filter
     * @param bool $toArray
     * @return mixed
     */
    public function findBy(array $filter, bool $toArray = true);

    /**
     * Find one record by filter.
     * @param array $filter
     * @param bool $toArray
     * @return mixed
     */
    public function findOneBy(array $filter, bool $toArray = true);

    /**
     * Paginate results.
     * @param int $page
     * @return mixed
     */
    public function paginate($page);

    /**
     * Update records by condition.
     * @param array $attributes
     * @param array $params
     * @return void
     */
    public function updateWhere(array $attributes = [], array $params = []): void;

    /**
     * Delete records by condition.
     * @param array $filter
     * @return void
     */
    public function deleteBy(array $filter): void;

    /**
     * Find records where in.
     * @param array $filter
     * @param bool $toArray
     * @return mixed
     */
    public function findWhereIn(array $filter, bool $toArray = true);

    /**
     * Delete records where in.
     * @param array $filter
     * @return void
     */
    public function deleteWhereIn(array $filter): void;

    /**
     * Update or create a record.
     * @param array $attributes
     * @param array $params
     * @return void
     */
    public function updateOrCreate(array $attributes = [], array $params = []): void;

    /**
     * Count records.
     * @param array $filter
     * @return int
     */
    public function countRecord(array $filter = []): int;

    /**
     * Find records by IDs.
     * @param array $ids
     * @param array $filter
     * @param bool $returnOnlyIds
     * @return array
     */
    public function findByIds(array $ids, array $filter = [], bool $returnOnlyIds = false): array;

    /**
     * Update where in.
     * @param string $column
     * @param array $values
     * @param array $attributes
     * @param array $whereConditions
     * @return void
     */
    public function updateWhereIn(string $column, array $values, array $attributes, array $whereConditions = []): void;

    /**
     * Update where not in.
     * @param string $column
     * @param array $values
     * @param array $attributes
     * @param array $whereConditions
     * @return void
     */
    public function updateWhereNotIn(string $column, array $values, array $attributes, array $whereConditions = []): void;

    /**
     * Delete not in IDs.
     * @param string $columnName
     * @param int $value
     * @param array $ids
     * @param string $primaryKey
     * @return void
     */
    public function deleteNotInIds(string $columnName, int $value, array $ids, string $primaryKey = 'id'): void;

    /**
     * Sync many-to-many.
     * @param Model $model
     * @param string $relation
     * @param array $attributes
     * @param bool $detaching
     * @return mixed
     */
    public function sync(Model $model, string $relation, array $attributes, bool $detaching = true);

    /**
     * Attach many-to-many.
     * @param Model $model
     * @param string $relation
     * @param array $attributes
     * @return mixed
     */
    public function attach(Model $model, string $relation, array $attributes);

    /**
     * Detach many-to-many.
     * @param Model $model
     * @param string $relation
     * @param array $attributes
     * @return mixed
     */
    public function detach(Model $model, string $relation, array $attributes = []);
}
