<?php

namespace App\Support;

use Illuminate\Http\UploadedFile;

/**
 * Human-readable PHP upload error messages.
 * (Thông báo lỗi upload file từ PHP)
 */
final class UploadedFileErrors
{
    public static function message(UploadedFile $file, int $maxKilobytes = 5120): ?string
    {
        if ($file->isValid()) {
            return null;
        }

        return match ($file->getError()) {
            UPLOAD_ERR_INI_SIZE => 'Ảnh vượt quá giới hạn upload của server (upload_max_filesize). '
                .'Tối đa '.self::formatKilobytes($maxKilobytes).' theo ứng dụng — hãy nén ảnh hoặc tăng upload_max_filesize trong php.ini.',
            UPLOAD_ERR_FORM_SIZE => 'Ảnh vượt quá dung lượng cho phép (tối đa '.self::formatKilobytes($maxKilobytes).').',
            UPLOAD_ERR_PARTIAL => 'Ảnh chỉ tải lên được một phần. Vui lòng thử lại.',
            UPLOAD_ERR_NO_FILE => 'Không nhận được file ảnh. Vui lòng chọn lại.',
            UPLOAD_ERR_NO_TMP_DIR, UPLOAD_ERR_CANT_WRITE, UPLOAD_ERR_EXTENSION => 'Server không ghi được file tạm khi upload ảnh.',
            default => 'Không tải được ảnh lên server. Vui lòng thử ảnh nhỏ hơn (jpg/png/webp).',
        };
    }

    private static function formatKilobytes(int $kb): string
    {
        return $kb >= 1024 ? round($kb / 1024, 1).'MB' : $kb.'KB';
    }
}
