<?php

namespace App\Repositories\Eloquent;

use App\Repositories\Interfaces\RepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * Class BaseRepository
 * Abstract base repository providing common Eloquent CRUD implementation.
 * (Lớp Repository cơ sở cung cấp các thực thi CRUD Eloquent chung)
 */
abstract class BaseRepository implements RepositoryInterface
{
    /** @var Model */
    protected $model;

    /**
     * BaseRepository constructor.
     * (Khởi tạo BaseRepository)
     */
    public function __construct()
    {
        $this->setModel();
    }

    /**
     * Get the associated model class name.
     * (Lấy tên lớp Model liên kết)
     *
     * @return string
     */
    abstract public function getModel();

    /**
     * Set the associated model instance.
     * (Thiết lập instance Model liên kết)
     */
    public function setModel(): void
    {
        $this->model = app()->make($this->getModel());
    }

    /**
     * Get all records.
     * (Lấy tất cả bản ghi)
     *
     * @return Collection
     */
    public function all()
    {
        return $this->model->all();
    }

    /**
     * Create a new record.
     * (Tạo bản ghi mới)
     *
     * @return Model
     */
    public function create(array $attributes = [])
    {
        return $this->model->create($attributes);
    }

    /**
     * Insert multiple records at once.
     * (Chèn nhiều bản ghi cùng lúc)
     *
     * @return bool
     */
    public function insert(array $attributes)
    {
        return $this->model->insert($attributes);
    }

    /**
     * Update a record by ID.
     * (Cập nhật bản ghi theo ID)
     *
     * @param  mixed  $id
     * @return bool
     */
    public function update($id, array $attributes = [])
    {
        $record = $this->find($id);
        if (! $record) {
            return false;
        }

        return $record->update($attributes);
    }

    /**
     * Delete a record by ID.
     * (Xóa bản ghi theo ID)
     *
     * @param  mixed  $id
     * @return int
     */
    public function delete($id)
    {
        return $this->model->destroy($id);
    }

    /**
     * Find a record by ID or throw exception.
     * (Tìm bản ghi theo ID hoặc ném ngoại lệ)
     *
     * @param  mixed  $id
     * @return Model
     */
    public function show($id)
    {
        return $this->model->findOrFail($id);
    }

    /**
     * Find a record by ID.
     * (Tìm bản ghi theo ID)
     *
     * @param  mixed  $id
     * @return Model|null
     */
    public function find($id)
    {
        return $this->model->newQuery()->find($id);
    }

    /**
     * Find a record by ID with specific columns only.
     * (Tìm bản ghi theo ID với các cột cụ thể)
     *
     * @param  mixed  $id
     * @return Model|null
     */
    public function findOnlyColumn($id, array $columns = ['*'])
    {
        return $this->model->newQuery()->select($columns)->where('id', $id)->first();
    }

    /**
     * Get the first record matching the given attributes.
     * (Lấy bản ghi đầu tiên khớp với các thuộc tính)
     */
    public function firstWhere(array $where): ?Model
    {
        return $this->model->newQuery()->where($where)->first();
    }

    /**
     * Get all records matching the given attributes.
     * (Lấy tất cả bản ghi khớp với các thuộc tính)
     */
    public function getWhere(array $where): Collection
    {
        return $this->model->newQuery()->where($where)->get();
    }

    /**
     * Count records, optionally filtered by conditions.
     * (Đếm số bản ghi, có thể lọc theo điều kiện)
     */
    public function count(array $where = []): int
    {
        return $this->model->newQuery()->where($where)->count();
    }

    /**
     * Check if any record matches the given conditions.
     * (Kiểm tra xem có bản ghi nào khớp với điều kiện không)
     */
    public function exists(array $where): bool
    {
        return $this->model->newQuery()->where($where)->exists();
    }

    /**
     * Increment a column value for a record.
     * (Tăng giá trị cột của một bản ghi)
     */
    public function increment(int $id, string $column, int $amount = 1, array $extraConditions = []): bool
    {
        $query = $this->model->newQuery()->where('id', $id);

        if (! empty($extraConditions)) {
            $query->where($extraConditions);
        }

        return (bool) $query->increment($column, $amount);
    }

    /**
     * Decrement a column value for a record.
     * (Giảm giá trị cột của một bản ghi)
     */
    public function decrement(int $id, string $column, int $amount = 1, array $extraConditions = []): bool
    {
        $query = $this->model->newQuery()->where('id', $id);

        if (! empty($extraConditions)) {
            $query->where($extraConditions);
        }

        return (bool) $query->decrement($column, $amount);
    }

    /**
     * Sync a many-to-many relationship.
     * (Đồng bộ quan hệ nhiều-nhiều)
     *
     * @param  Model|int  $idOrModel
     * @return mixed
     */
    public function sync($idOrModel, string $relation, array $attributes, bool $detaching = true)
    {
        $model = $idOrModel instanceof Model ? $idOrModel : $this->find($idOrModel);

        if (! $model) {
            return null;
        }

        return $model->{$relation}()->sync($attributes, $detaching);
    }

    /**
     * Attach records to a many-to-many relationship.
     * (Gắn bản ghi vào quan hệ nhiều-nhiều)
     *
     * @param  Model|int  $idOrModel
     * @return mixed
     */
    public function attach($idOrModel, string $relation, array $attributes)
    {
        $model = $idOrModel instanceof Model ? $idOrModel : $this->find($idOrModel);

        if (! $model) {
            return null;
        }

        return $model->{$relation}()->attach($attributes);
    }

    /**
     * Detach records from a many-to-many relationship.
     * (Gỡ bản ghi khỏi quan hệ nhiều-nhiều)
     *
     * @param  Model|int  $idOrModel
     * @return mixed
     */
    public function detach($idOrModel, string $relation, array $attributes = [])
    {
        $model = $idOrModel instanceof Model ? $idOrModel : $this->find($idOrModel);

        if (! $model) {
            return null;
        }

        return $model->{$relation}()->detach($attributes);
    }

    /**
     * Generate a unique slug for the model.
     * (Tạo slug duy nhất cho model)
     */
    public function generateUniqueSlug(string $value, string $column = 'slug', ?int $ignoreId = null): string
    {
        $base = Str::slug($value);
        $slug = $base;
        $i = 2;

        while (true) {
            $query = $this->model->newQuery()->where($column, $slug);

            if ($ignoreId !== null) {
                $query->where($this->model->getKeyName(), '!=', $ignoreId);
            }

            if (! $query->exists()) {
                return $slug;
            }

            $slug = $base.'-'.$i;
            $i++;
        }
    }

    // -------------------------------------------------------------------------
    // Methods below are available to child repositories but NOT part of the
    // public interface contract. Child repos should use $this->model->newQuery()
    // directly for domain-specific queries.
    // -------------------------------------------------------------------------

    /**
     * Find one record by key-value filters.
     * (Tìm một bản ghi theo bộ lọc key-value)
     *
     * @return Model|array|null
     */
    public function findOneBy(array $filter, bool $toArray = true)
    {
        $data = $this->model->newQuery()->where($filter)->first();

        if (! $toArray) {
            return $data;
        }

        return $data ? $data->toArray() : [];
    }

    /**
     * Find records by key-value filters.
     * (Tìm các bản ghi theo bộ lọc key-value)
     *
     * @return Collection|array
     */
    public function findBy(array $filter, bool $toArray = true)
    {
        $result = $this->model->newQuery()->where($filter)->get();

        if (! $toArray) {
            return $result;
        }

        return $result ? $result->toArray() : [];
    }

    /**
     * Paginate all records.
     * (Phân trang tất cả bản ghi)
     */
    public function paginate(int $perPage): LengthAwarePaginator
    {
        return $this->model->newQuery()->paginate($perPage);
    }

    /**
     * Update records matching conditions.
     * (Cập nhật các bản ghi khớp với điều kiện)
     */
    public function updateWhere(array $attributes = [], array $params = []): int
    {
        return $this->model->newQuery()->where($attributes)->update($params);
    }

    /**
     * Delete records matching conditions.
     * (Xóa các bản ghi khớp với điều kiện)
     */
    public function deleteBy(array $filter): int
    {
        return $this->model->newQuery()->where($filter)->delete();
    }

    /**
     * Find records where a column is in an array of values.
     * (Tìm bản ghi có cột nằm trong mảng giá trị)
     *
     * @return Collection|array
     */
    public function findWhereIn(array $filter, bool $toArray = true)
    {
        $data = $this->model->newQuery()->whereIn($filter['column'], $filter['values'])->get();

        if (! $toArray) {
            return $data;
        }

        return $data ? $data->toArray() : [];
    }

    /**
     * Delete records where a column is in an array of values.
     * (Xóa bản ghi có cột nằm trong mảng giá trị)
     */
    public function deleteWhereIn(array $filter): int
    {
        return $this->model->newQuery()->whereIn($filter['column'], $filter['values'])->delete();
    }

    /**
     * Update or create a record.
     * (Cập nhật hoặc tạo mới bản ghi)
     */
    public function updateOrCreate(array $attributes = [], array $params = []): void
    {
        $this->model->updateOrCreate($attributes, $params);
    }

    /**
     * Count records with flexible filter syntax.
     * (Đếm bản ghi với cú pháp filter linh hoạt)
     */
    public function countRecord(array $filter = []): int
    {
        $query = $this->model->newQuery();

        foreach ($filter as $key => $value) {
            if (is_array($value) && isset($value['operator'], $value['value'])) {
                $query->where($key, $value['operator'], $value['value']);
            } else {
                $query->where($key, $value);
            }
        }

        return $query->count();
    }

    /**
     * Find records by array of IDs with optional filters and chunking.
     * (Tìm bản ghi theo mảng ID với filter tùy chọn và hỗ trợ chunking)
     */
    public function findByIds(array $ids, array $filter = [], bool $returnOnlyIds = false): array
    {
        if (empty($ids)) {
            return [];
        }

        $applyFilter = function ($query) use ($filter) {
            foreach ($filter as $key => $value) {
                $query->where($key, $value);
            }

            return $query;
        };

        $chunkSize = 1000;

        if (count($ids) > $chunkSize) {
            $results = collect($ids)
                ->chunk($chunkSize)
                ->flatMap(function ($chunk) use ($applyFilter) {
                    return $applyFilter($this->model->newQuery()->whereIn('id', $chunk))->get();
                });
        } else {
            $results = $applyFilter($this->model->newQuery()->whereIn('id', $ids))->get();
        }

        return $returnOnlyIds
            ? $results->pluck('id')->toArray()
            : $results->toArray();
    }

    /**
     * Update records where a column is in an array of values.
     * (Cập nhật bản ghi có cột nằm trong mảng giá trị)
     */
    public function updateWhereIn(string $column, array $values, array $attributes, array $whereConditions = []): void
    {
        $query = $this->model->newQuery()->whereIn($column, $values);

        if (! empty($whereConditions)) {
            $query->where($whereConditions);
        }

        $query->update($attributes);
    }

    /**
     * Update records where a column is NOT in an array of values.
     * (Cập nhật bản ghi có cột KHÔNG nằm trong mảng giá trị)
     */
    public function updateWhereNotIn(string $column, array $values, array $attributes, array $whereConditions = []): void
    {
        $query = $this->model->newQuery()->whereNotIn($column, $values);

        if (! empty($whereConditions)) {
            $query->where($whereConditions);
        }

        $query->update($attributes);
    }

    /**
     * Delete records whose ID is not in the given array.
     * (Xóa bản ghi có ID KHÔNG nằm trong mảng cho trước)
     */
    public function deleteNotInIds(string $columnName, int $value, array $ids, string $primaryKey = 'id'): void
    {
        $this->model->newQuery()
            ->where($columnName, $value)
            ->whereNotIn($primaryKey, $ids)
            ->delete();
    }
}
