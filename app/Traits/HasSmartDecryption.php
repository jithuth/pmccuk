<?php

namespace App\Traits;

use Illuminate\Support\Facades\Crypt;

trait HasSmartDecryption
{
    /**
     * Override the default getAttribute to automatically intercept
     * and decrypt legacy encrypted data.
     */
    public function getAttribute($key)
    {
        $value = parent::getAttribute($key);

        // We only care about potential encrypted strings
        if (is_string($value) && str_starts_with($value, 'eyJ')) {
            try {
                // Determine if it's a field we should try to decrypt
                // (Optional: check if the field exists in a $smart_decrypts array)
                // For now, we try-catch any string starting with eyJ
                return Crypt::decryptString($value);
            } catch (\Exception $e) {
                // If decryption fails, just return the raw string
                return $value;
            }
        }

        return $value;
    }
}
