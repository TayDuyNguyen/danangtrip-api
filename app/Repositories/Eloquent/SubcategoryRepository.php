<?php

namespace App\Repositories\Eloquent;

use App\Models\Subcategory;
use App\Repositories\Interfaces\SubcategoryRepositoryInterface;

final class SubcategoryRepository extends BaseRepository implements SubcategoryRepositoryInterface
{
    public function getModel()
    {
        return Subcategory::class;
    }
}
