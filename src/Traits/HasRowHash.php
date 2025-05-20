<?php

namespace MajorPhyoSan\PreventSameShit\Traits;

use Exception;

use Illuminate\Support\Facades\Config;

trait HasRowHash
{
    public static function bootHasRowHash()
    {
        static::creating(function ($model) {
            $hashColumnName = $this->getHashColumnName();
            $model[$hashColumnName] = $model->generateRowHash($model->getIncludedHashColumns(), $model->getExcludedHashColumns());

            // Optional: Prevent inserting duplicate hash
            if (self::where('record_hash', $model[$hashColumnName])->exists()) {
                throw new Exception('Duplicate detected by row hash on create.');
            }
        });

        static::updating(function ($model) {
            $hashColumnName = $this->getHashColumnName();
            $newHash = $model->generateRowHash($model->getExcludedHashColumns());

            // Skip if hash remains the same
            if ($model[$hashColumnName] === $newHash) {
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

            $model[$hashColumnName] = $newHash;
        });
    }

    public function getHashColumnName(): string
    {
        return property_exists($this, 'recordHashColumn')
            ? $this->recordHashColumn
            : 'record_hash';
    }

    public function getExcludedHashColumns(): array
    {
        return property_exists($this, 'excludedFromHash')
            ? $this->excludedFromHash
            : [];
    }

    public function getIncludedHashColumns(): array
    {
        return property_exists($this, 'includedInHash')
            ? $this->includedInHash
            : [];
    }

    /**
     * Generate the row hash using model attributes.
     */
    public function generateRowHash(array $include = [], array $exclude = []): string
    {
        // 1. Get model attributes and exclude columns
        $data = $this->attributesToArray();
        unset($data['id'], $data['record_hash'], $data['created_at'], $data['updated_at']);

        if (!empty($include)) {
            $data = array_intersect_key($data, array_flip($include));
        }

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
