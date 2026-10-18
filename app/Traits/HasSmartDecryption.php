<?php

namespace App\Traits;

use Illuminate\Support\Facades\Crypt;

trait HasSmartDecryption
{
    /**
     * Override the default getAttribute to automatically intercept
     * and decrypt encrypted data.
     */
    public function getAttribute($key)
    {
        $value = parent::getAttribute($key);

        // We only care about potential encrypted strings
        if (is_string($value) && str_starts_with($value, 'eyJ')) {
            try {
                return Crypt::decryptString($value);
            } catch (\Exception $e) {
                // If decryption fails, return the raw string
                return $value;
            }
        }

        return $value;
    }

    /**
     * Override setAttribute to automatically encrypt designated attributes when assigned.
     */
    public function setAttribute($key, $value)
    {
        if (is_string($value) && !empty($value) && !str_starts_with($value, 'eyJ')) {
            if ($this->isEncryptableAttribute($key)) {
                $value = Crypt::encryptString($value);
            }
        }

        return parent::setAttribute($key, $value);
    }

    /**
     * Determine if an attribute should be encrypted.
     */
    public function isEncryptableAttribute(string $key): bool
    {
        if (property_exists($this, 'encryptable') && is_array($this->encryptable)) {
            return in_array($key, $this->encryptable, true);
        }
        return false;
    }
}
