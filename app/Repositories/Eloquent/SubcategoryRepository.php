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
}
