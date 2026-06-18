<?php

namespace App\Repositories\Eloquent;

use App\Models\Setting;
use App\Repositories\Interfaces\SettingRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Class SettingRepository
 * (Thực thi Repository cho Cấu hình Website)
 */
final class SettingRepository extends BaseRepository implements SettingRepositoryInterface
{
    /**
     * Get the model class.
     */
    public function getModel(): string
    {
        return Setting::class;
    }

    /**
     * Get all public website settings.
     */
    public function getPublicSettings(): Collection
    {
        $query = $this->model->newQuery();

        $this->whereBooleanColumn($query, 'is_public', true);

        return $query->get();
    }

    /**
     * Get all settings for admin screen.
     */
    public function getAdminSettings(): Collection
    {
        return $this->model->newQuery()->get();
    }

    /**
     * Save multiple configurations at once under a safe database transaction.
     */
    public function saveSettings(array $settings): bool
    {
        return DB::transaction(function () use ($settings) {
            foreach ($settings as $section => $values) {
                if (! is_array($values)) {
                    continue;
                }

                foreach ($values as $key => $value) {
                    $dbKey = "{$section}.{$key}";

                    // Determine value_type automatically
                    $valueType = 'string';
                    if (is_bool($value)) {
                        $valueType = 'boolean';
                        $value = $value ? 'true' : 'false';
                    } elseif (is_numeric($value)) {
                        $valueType = 'number';
                        $value = (string) $value;
                    } elseif (is_array($value)) {
                        $valueType = 'json';
                        $value = json_encode($value);
                    }

                    $this->model->newQuery()->updateOrCreate(
                        ['key' => $dbKey],
                        [
                            'value' => $value,
                            'value_type' => $valueType,
                            // Ensure seeded public configurations stay public
                            // (Thao tác update không thay đổi thuộc tính is_public mặc định)
                        ]
                    );
                }
            }

            return true;
        });
    }
}
