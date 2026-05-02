<?php

namespace App\Services;

/**
 * Static district list for Da Nang (config-driven).
 * (Danh sách quận/huyện Đà Nẵng — nguồn config)
 */
final class DistrictService
{
    /**
     * Return all predefined districts.
     * (Trả về toàn bộ quận/huyện định nghĩa sẵn)
     *
     * @return list<array{id: int, name: string, slug: string}>
     */
    public function listAll(): array
    {
        return config('danang_districts', []);
    }
}
