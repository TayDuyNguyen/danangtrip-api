<?php

namespace App\Services\Chat;

final class IntentConsistencyService
{
    /**
     * Xác định xem có nên bỏ qua kết quả rule-based và bắt buộc gọi AI NLU hay không.
     * Dựa trên các quy tắc cấu hình kiểm tra tính không nhất quán của dữ liệu thực thể.
     *
     * @param  string  $intent  Ý định nhận diện bởi bộ lọc rule-based
     * @param  array<string,mixed>  $entities  Các thực thể đã trích xuất được từ rule-based
     * @return bool Trả về true nếu bắt buộc phải gọi AI để trích xuất lại
     */
    public function shouldForceAi(string $intent, array $entities): bool
    {
        $rules = config('chat_consistency', []);

        if (! isset($rules[$intent])) {
            return false;
        }

        $rule = $rules[$intent];
        $abnormalCount = 0;

        foreach (($rule['abnormal'] ?? []) as $field) {
            if (isset($entities[$field]) && $entities[$field] !== null && $entities[$field] !== '') {
                $abnormalCount++;
            }
        }

        return $abnormalCount >= ($rule['threshold'] ?? 1);
    }
}
