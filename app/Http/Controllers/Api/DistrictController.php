<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

/**
 * Class DistrictController
 * Returns static list of districts in Da Nang city.
 * (Trả về danh sách quận/huyện tĩnh của thành phố Đà Nẵng)
 */
final class DistrictController extends Controller
{
    /**
     * Get the list of districts in Da Nang.
     * (Lấy danh sách quận/huyện tại Đà Nẵng)
     */
    public function index(): JsonResponse
    {
        $districts = [
            ['id' => 1, 'name' => 'Hải Châu', 'slug' => 'hai-chau'],
            ['id' => 2, 'name' => 'Thanh Khê', 'slug' => 'thanh-khe'],
            ['id' => 3, 'name' => 'Sơn Trà', 'slug' => 'son-tra'],
            ['id' => 4, 'name' => 'Ngũ Hành Sơn', 'slug' => 'ngu-hanh-son'],
            ['id' => 5, 'name' => 'Liên Chiểu', 'slug' => 'lien-chieu'],
            ['id' => 6, 'name' => 'Cẩm Lệ', 'slug' => 'cam-le'],
            ['id' => 7, 'name' => 'Hòa Vang', 'slug' => 'hoa-vang'],
            ['id' => 8, 'name' => 'Hoàng Sa', 'slug' => 'hoang-sa'],
        ];

        return $this->success($districts);
    }
}
