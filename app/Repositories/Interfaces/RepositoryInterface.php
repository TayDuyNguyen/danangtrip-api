<?php

namespace App\Repositories\Interfaces;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * Interface RepositoryInterface
 * Base contract defining essential CRUD operations for all repositories.
 * (Interface cơ sở định nghĩa các thao tác CRUD thiết yếu cho tất cả repository)
 */
interface RepositoryInterface
{
    /**
     * Get all records.
     * (Lấy tất cả bản ghi)
     *
     * @return Collection
     */
    public function all();

    /**
     * Create a new record.
     * (Tạo bản ghi mới)
     *
     * @return Model
     */
    public function create(array $attributes = []);

    /**
     * Insert multiple records at once.
     * (Chèn nhiều bản ghi cùng lúc)
     *
     * @return bool
     */
    public function insert(array $attributes);

    /**
     * Update a record by ID.
     * (Cập nhật bản ghi theo ID)
     *
     * @param  mixed  $id
     * @return bool
     */
    public function update($id, array $attributes = []);

    /**
     * Delete a record by ID.
     * (Xóa bản ghi theo ID)
     *
     * @param  mixed  $id
     * @return int
     */
    public function delete($id);

    /**
     * Find a record by ID or throw exception.
     * (Tìm bản ghi theo ID hoặc ném ngoại lệ)
     *
     * @param  mixed  $id
     * @return Model
     */
    public function show($id);

    /**
     * Find a record by ID.
     * (Tìm bản ghi theo ID)
     *
     * @param  mixed  $id
     * @return Model|null
     */
    public function find($id);

    /**
     * Find a record by ID with specific columns only.
     * (Tìm bản ghi theo ID với các cột cụ thể)
     *
     * @param  mixed  $id
     * @return Model|null
     */
    public function findOnlyColumn($id, array $columns = ['*']);

    /**
     * Get the first record matching the given attributes.
     * (Lấy bản ghi đầu tiên khớp với các thuộc tính)
     */
    public function firstWhere(array $where): ?Model;

    /**
     * Get all records matching the given attributes.
     * (Lấy tất cả bản ghi khớp với các thuộc tính)
     */
    public function getWhere(array $where): Collection;

    /**
     * Count records, optionally filtered by conditions.
     * (Đếm số bản ghi, có thể lọc theo điều kiện)
     */
    public function count(array $where = []): int;

    /**
     * Check if any record matches the given conditions.
     * (Kiểm tra xem có bản ghi nào khớp với điều kiện không)
     */
    public function exists(array $where): bool;

    /**
     * Increment a column value for a record.
     * (Tăng giá trị cột của một bản ghi)
     */
    public function increment(int $id, string $column, int $amount = 1, array $extraConditions = []): bool;

    /**
     * Decrement a column value for a record.
     * (Giảm giá trị cột của một bản ghi)
     */
    public function decrement(int $id, string $column, int $amount = 1, array $extraConditions = []): bool;

    /**
     * Sync a many-to-many relationship.
     * (Đồng bộ quan hệ nhiều-nhiều)
     *
     * @param  Model|int  $idOrModel
     * @return mixed
     */
    public function sync($idOrModel, string $relation, array $attributes, bool $detaching = true);

    /**
     * Attach records to a many-to-many relationship.
     * (Gắn bản ghi vào quan hệ nhiều-nhiều)
     *
     * @param  Model|int  $idOrModel
     * @return mixed
     */
    public function attach($idOrModel, string $relation, array $attributes);

    /**
     * Detach records from a many-to-many relationship.
     * (Gỡ bản ghi khỏi quan hệ nhiều-nhiều)
     *
     * @param  Model|int  $idOrModel
     * @return mixed
     */
    public function detach($idOrModel, string $relation, array $attributes = []);

    /**
     * Generate a unique slug for the model.
     * (Tạo slug duy nhất cho model)
     */
    public function generateUniqueSlug(string $value, string $column = 'slug', ?int $ignoreId = null): string;
}
