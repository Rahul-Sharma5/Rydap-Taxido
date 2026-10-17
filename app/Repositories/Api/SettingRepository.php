<?php

namespace App\Repositories\Api;

use App\Models\Setting;
use Prettus\Repository\Eloquent\BaseRepository;

class SettingRepository extends BaseRepository
{
    function model()
    {
        return Setting::class;
    }

    public function index()
    {
        $settings = $this->model->latest('created_at')->first();

        if (! $settings) {
            return null;
        }

        $values = $settings->values;
        $defaultLanguageLocale = data_get($values, 'general.default_language.locale');

        return [
            'id' => $settings->id,
            'values' => [
                'general' => [
                    'default_language' => $defaultLanguageLocale !== null
                        ? ['locale' => $defaultLanguageLocale]
                        : null,
                ],
                'maintenance' => [
                    'maintenance_mode' => data_get($values, 'maintenance.maintenance_mode'),
                ],
            ],
        ];
    }
}
