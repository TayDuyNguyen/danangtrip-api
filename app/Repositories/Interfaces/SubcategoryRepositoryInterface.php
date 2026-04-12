<?php

namespace App\Repositories\Interfaces;

/**
 * Interface SubcategoryRepositoryInterface
 * Define standard operations for Subcategory repository.
 * (Định nghĩa các thao tác tiêu chuẩn cho repository Danh mục con)
 */
interface SubcategoryRepositoryInterface extends RepositoryInterface
{
    /**
     * Update the status of a subcategory.
     * (Cập nhật trạng thái danh mục con)
     */
    public function updateStatus(int $id, string $status): bool;
}
