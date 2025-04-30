<?php

namespace MajorPhyoSan\PreventSameShit\Traits;

use Exception;

use Illuminate\Support\Facades\Config;

trait HasRowHash
{
    public static function bootHasRowHash()
    {
        static::creating(function ($model) {
            $model->record_hash = $model->generateRowHash($model->getExcludedHashColumns());

            // Optional: Prevent inserting duplicate hash
            if (self::where('record_hash', $model->record_hash)->exists()) {
                throw new Exception('Duplicate detected by row hash on create.');
            }
        });

        static::updating(function ($model) {
            $newHash = $model->generateRowHash($model->getExcludedHashColumns());

            // Skip if hash remains the same
            if ($model->record_hash === $newHash) {
                return;
            }

            // Optional: prevent saving duplicate hash from other records
            if (
                self::where('record_hash', $newHash)
                    ->where('id', '!=', $model->id)
                    ->exists()
            ) {
                throw new Exception('Duplicate detected by row hash on update.');
            }

            $model->record_hash = $newHash;
        });
    }

    public function getExcludedHashColumns(): array
    {
        return property_exists($this, 'excludedFromHash')
            ? $this->excludedFromHash
            : [];
    }

    /**
     * Generate the row hash using model attributes.
     */
    public function generateRowHash(array $exclude = []): string
    {
        // 1. Get model attributes and exclude columns
        $data = $this->attributesToArray();
        unset($data['id'], $data['record_hash'], $data['created_at'], $data['updated_at']);
        foreach ($exclude as $column) {
            unset($data[$column]);
        }
        // 2. Sort for consistent hashing
        ksort($data);

         // 3. Serialize to string (JSON)
        $payload = json_encode($data);

        // 4. Generate HMAC using SHA-256
        return hash_hmac('sha256', $payload, Config::get('app.key'));
    }
}
