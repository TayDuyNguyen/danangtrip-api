<?php

namespace App\Repositories\Eloquent;

use App\Repositories\Interfaces\RepositoryInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

/**
 * Class BaseRepository
 * Abstract base repository providing common Eloquent implementation.
 * (Lớp Repository cơ sở cung cấp các thực thi Eloquent chung)
 */
abstract class BaseRepository implements RepositoryInterface
{
    /** @var Model */
    protected $model;

    /** @var Builder */
    protected $query;

    /**
     * BaseRepository constructor.
     * (Khởi tạo BaseRepository)
     */
    public function __construct()
    {
        $this->setModel();
        $this->query = $this->model->newQuery();
    }

    /**
     * Get the associated model class name.
     * (Lấy tên lớp Model liên kết)
     * 
     * @return string
     */
    abstract public function getModel();

    /**
     * Set the associated model.
     * (Thiết lập Model liên kết)
     *
     * @return void
     */
    public function setModel(): void
    {
        $this->model = app()->make($this->getModel());
    }

    /**
     * Get all instances of model.
     * (Lấy tất cả các bản ghi của model)
     * 
     * @return Collection
     */
    public function all()
    {
        return $this->model->all();
    }

    /**
     * Create a new record in the database.
     * (Tạo một bản ghi mới trong cơ sở dữ liệu)
     * 
     * @param array $attributes
     * @return Model
     */
    public function create(array $attributes = [])
    {
        return $this->model->create($attributes);
    }

    /**
     * Insert multiple records.
     * (Chèn nhiều bản ghi cùng lúc)
     * 
     * @param array $attributes
     * @return bool
     */
    public function insert(array $attributes)
    {
        return $this->model->insert($attributes);
    }

    /**
     * Update record in the database.
     * (Cập nhật bản ghi trong cơ sở dữ liệu)
     * 
     * @param mixed $id
     * @param array $attributes
     * @return bool
     */
    public function update($id, array $attributes = [])
    {
        $record = $this->find($id);
        if (!$record) {
            return false;
        }
        return $record->update($attributes);
    }

    /**
     * Remove record from the database.
     * (Xóa bản ghi khỏi cơ sở dữ liệu)
     * 
     * @param mixed $id
     * @return int
     */
    public function delete($id)
    {
        return $this->model->destroy($id);
    }

    /**
     * Show the record with the given id or throw exception.
     * (Hiển thị bản ghi với ID cho trước hoặc ném ra ngoại lệ)
     * 
     * @param mixed $id
     * @return Model
     */
    public function show($id)
    {
        return $this->model->findOrFail($id);
    }

    /**
     * Find the record with the given id.
     * (Tìm bản ghi với ID cho trước)
     * 
     * @param mixed $id
     * @return Model|null
     */
    public function find($id)
    {
        return $this->model->find($id);
    }

    /**
     * Find the record with specific columns.
     * (Tìm bản ghi với các cột cụ thể)
     * 
     * @param mixed $id
     * @param array $columns
     * @return Model|null
     */
    public function findOnlyColumn($id, array $columns = ['*'])
    {
        return $this->model->select($columns)->where('id', $id)->first();
    }

    /**
     * Get the first record.
     * (Lấy bản ghi đầu tiên)
     * 
     * @return Model|null
     */
    public function first()
    {
        return $this->model->first();
    }

    /**
     * Eager load database relationships.
     * (Tải trước các mối quan hệ cơ sở dữ liệu)
     * 
     * @param mixed $relations
     * @return $this
     */
    public function with($relations)
    {
        $this->query->with($relations);
        return $this;
    }

    /**
     * Sort results by column.
     * (Sắp xếp kết quả theo cột)
     * 
     * @param string $column
     * @param string $direction
     * @return $this
     */
    public function orderBy(string $column, string $direction = 'asc')
    {
        $this->query->orderBy($column, $direction);
        return $this;
    }

    /**
     * Limit the number of results.
     * (Giới hạn số lượng kết quả)
     * 
     * @param int $limit
     * @return $this
     */
    public function limit(int $limit)
    {
        $this->query->limit($limit);
        return $this;
    }

    /**
     * Get the underlying query builder.
     * (Lấy trình xây dựng truy vấn bên dưới)
     * 
     * @return Builder
     */
    public function getQuery()
    {
        return $this->query;
    }

    /**
     * Reset the query builder.
     * (Đặt lại trình xây dựng truy vấn)
     * 
     * @return Builder
     */
    public function clearQuery()
    {
        $this->query = $this->model->newQuery();
        return $this->query;
    }

    /**
     * Find records by filters.
     * (Tìm các bản ghi theo bộ lọc)
     * 
     * @param array $filter
     * @param bool $toArray
     * @return mixed
     */
    public function findBy(array $filter, bool $toArray = true)
    {
        $builder = $this->model->newQuery();
        foreach ($filter as $key => $val) {
            $builder->where($key, $val);
        }
        $find = $builder->get();

        if (!$toArray) {
            return $find;
        }
        return $find ? $find->toArray() : null;
    }

    /**
     * Find one record by filters.
     * (Tìm một bản ghi theo bộ lọc)
     * 
     * @param array $filter
     * @param bool $toArray
     * @return mixed
     */
    public function findOneBy(array $filter, bool $toArray = true)
    {
        $builder = $this->model->newQuery();
        foreach ($filter as $key => $val) {
            $builder->where($key, $val);
        }
        $data = $builder->first();

        if (!$toArray) {
            return $data;
        }
        return $data ? $data->toArray() : [];
    }

    /**
     * Paginate results.
     * (Phân trang kết quả)
     * 
     * @param int $page
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function paginate($page)
    {
        return $this->query->paginate($page);
    }

    /**
     * Update records matching filters.
     * (Cập nhật các bản ghi khớp với bộ lọc)
     * 
     * @param array $attributes
     * @param array $params
     * @return void
     */
    public function updateWhere(array $attributes = [], array $params = []): void
    {
        $this->model->where($attributes)->update($params);
    }

    /**
     * Delete records matching filters.
     * (Xóa các bản ghi khớp với bộ lọc)
     * 
     * @param array $filter
     * @return void
     */
    public function deleteBy(array $filter): void
    {
        $this->model->where($filter)->delete();
    }

    /**
     * Find records where column is in array of values.
     * (Tìm các bản ghi có cột nằm trong mảng các giá trị)
     * 
     * @param array $filter
     * @param bool $toArray
     * @return mixed
     */
    public function findWhereIn(array $filter, bool $toArray = true)
    {
        $data = $this->model->whereIn($filter['column'], $filter['values'])->get();

        if (!$toArray) {
            return $data;
        }
        return $data ? $data->toArray() : [];
    }

    /**
     * Delete records where column is in array of values.
     * (Xóa các bản ghi có cột nằm trong mảng các giá trị)
     * 
     * @param array $filter
     * @return void
     */
    public function deleteWhereIn(array $filter): void
    {
        $this->model->whereIn($filter['column'], $filter['values'])->delete();
    }

    /**
     * Update or create a record.
     * (Cập nhật hoặc tạo mới một bản ghi)
     * 
     * @param array $attributes
     * @param array $params
     * @return void
     */
    public function updateOrCreate(array $attributes = [], array $params = []): void
    {
        $this->model->updateOrCreate($attributes, $params);
    }

    /**
     * Count records matching filters.
     * (Đếm số lượng bản ghi khớp với bộ lọc)
     * 
     * @param array $filter
     * @return int
     */
    public function countRecord(array $filter = []): int
    {
        $query = $this->model->newQuery();

        foreach ($filter as $key => $value) {
            if (is_array($value) && isset($value['operator']) && isset($value['value'])) {
                $query->where($key, $value['operator'], $value['value']);
            } else {
                $query->where($key, $value);
            }
        }

        return $query->count();
    }

    /**
     * Find records by array of IDs with chunking support.
     * (Tìm các bản ghi theo mảng ID với hỗ trợ chia nhỏ dữ liệu)
     * 
     * @param array $ids
     * @param array $filter
     * @param bool $returnOnlyIds
     * @return array
     */
    public function findByIds(array $ids, array $filter = [], bool $returnOnlyIds = false): array
    {
        if (empty($ids)) {
            return [];
        }

        $chunkSize = 1000; // Default chunk size

        $applyFilter = function ($query) use ($filter) {
            foreach ($filter as $key => $value) {
                $query->where($key, $value);
            }
            return $query;
        };

        $results = collect();

        if (count($ids) > $chunkSize) {
            $results = collect($ids)
                ->chunk($chunkSize)
                ->flatMap(function ($chunk) use ($applyFilter) {
                    $query = $applyFilter($this->model->newQuery()->whereIn('id', $chunk));
                    return $query->get();
                });
        } else {
            $query = $this->model->newQuery()->whereIn('id', $ids);
            $results = $applyFilter($query)->get();
        }

        return $returnOnlyIds
            ? $results->pluck('id')->toArray()
            : $results->toArray();
    }

    /**
     * Update where in.
     * (Cập nhật các bản ghi có cột nằm trong mảng giá trị)
     * 
     * @param string $column
     * @param array $values
     * @param array $attributes
     * @param array $whereConditions
     * @return void
     */
    public function updateWhereIn(string $column, array $values, array $attributes, array $whereConditions = []): void
    {
        $query = $this->model->whereIn($column, $values);
        if (!empty($whereConditions)) {
            $query->where($whereConditions);
        }
        $query->update($attributes);
    }

    /**
     * Update where not in.
     * (Cập nhật các bản ghi có cột KHÔNG nằm trong mảng giá trị)
     * 
     * @param string $column
     * @param array $values
     * @param array $attributes
     * @param array $whereConditions
     * @return void
     */
    public function updateWhereNotIn(string $column, array $values, array $attributes, array $whereConditions = []): void
    {
        $query = $this->model->whereNotIn($column, $values);
        if (!empty($whereConditions)) {
            $query->where($whereConditions);
        }
        $query->update($attributes);
    }

    /**
     * Delete records not in IDs.
     * (Xóa các bản ghi KHÔNG nằm trong mảng ID)
     * 
     * @param string $columnName
     * @param int $value
     * @param array $ids
     * @param string $primaryKey
     * @return void
     */
    public function deleteNotInIds(string $columnName, int $value, array $ids, string $primaryKey = 'id'): void
    {
        $this->model->where($columnName, $value)
            ->whereNotIn($primaryKey, $ids)
            ->delete();
    }

    /**
     * Sync relationship data.
     * (Đồng bộ dữ liệu mối quan hệ)
     * 
     * @param Model $model
     * @param string $relation
     * @param array $attributes
     * @param bool $detaching
     * @return mixed
     */
    public function sync(Model $model, string $relation, array $attributes, bool $detaching = true)
    {
        return $model->{$relation}()->sync($attributes, $detaching);
    }

    /**
     * Attach relationship data.
     * (Gắn dữ liệu mối quan hệ)
     * 
     * @param Model $model
     * @param string $relation
     * @param array $attributes
     * @return mixed
     */
    public function attach(Model $model, string $relation, array $attributes)
    {
        return $model->{$relation}()->attach($attributes);
    }

    /**
     * Detach relationship data.
     * (Gỡ dữ liệu mối quan hệ)
     * 
     * @param Model $model
     * @param string $relation
     * @param array $attributes
     * @return mixed
     */
    public function detach(Model $model, string $relation, array $attributes = [])
    {
        return $model->{$relation}()->detach($attributes);
    }
}
