<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class MyanmarRegion implements Rule
{
    protected $addresses;

    /**
     * Create a new rule instance.
     */
    public function __construct()
    {
        $this->addresses = config('myanmar_addresses.addresses', []);
    }

    /**
     * Determine if the validation rule passes.
     */
    public function passes($attribute, $value): bool
    {
        if (empty($value)) {
            return true; // Allow null/empty
        }

        return isset($this->addresses[$value]);
    }

    /**
     * Get the validation error message.
     */
    public function message(): string
    {
        return 'The selected :attribute is not a valid Myanmar region.';
    }
}
