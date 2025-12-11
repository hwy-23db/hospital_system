<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class MyanmarDistrict implements Rule
{
    protected $addresses;
    protected $region;

    /**
     * Create a new rule instance.
     */
    public function __construct(?string $region = null)
    {
        $this->addresses = config('myanmar_addresses.addresses', []);
        $this->region = $region;
    }

    /**
     * Determine if the validation rule passes.
     */
    public function passes($attribute, $value): bool
    {
        if (empty($value)) {
            return true; // Allow null/empty
        }

        // If region is provided, validate district exists in that region
        if ($this->region) {
            if (!isset($this->addresses[$this->region])) {
                return false;
            }
            return isset($this->addresses[$this->region][$value]);
        }

        // If no region provided, check if district exists in any region
        foreach ($this->addresses as $region => $districts) {
            if (isset($districts[$value])) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get the validation error message.
     */
    public function message(): string
    {
        return 'The selected :attribute is not a valid Myanmar district.';
    }
}
