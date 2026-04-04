<?php

namespace App\Repositories\Eloquent;

use App\Models\Subcategory;
use App\Repositories\Interfaces\SubcategoryRepositoryInterface;

final class SubcategoryRepository extends BaseRepository implements SubcategoryRepositoryInterface
{
    /**
     * Get the associated model class name.
     * (Lấy tên lớp Model liên kết)
     *
     * @return string
     */
    public function getModel()
    {
        return Subcategory::class;
    }

    /**
     * Update the status of a subcategory.
     * (Cập nhật trạng thái danh mục con)
     */
    public function updateStatus(int $id, string $status): bool
    {
        return (bool) $this->model->where('id', $id)->update(['status' => $status]);
    }
}
