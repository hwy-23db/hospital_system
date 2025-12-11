<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class MyanmarTownship implements Rule
{
    protected $addresses;
    protected $region;
    protected $district;

    /**
     * Create a new rule instance.
     */
    public function __construct(?string $region = null, ?string $district = null)
    {
        $this->addresses = config('myanmar_addresses.addresses', []);
        $this->region = $region;
        $this->district = $district;
    }

    /**
     * Determine if the validation rule passes.
     */
    public function passes($attribute, $value): bool
    {
        if (empty($value)) {
            return true; // Allow null/empty
        }

        // If both region and district are provided, validate township exists in that district
        if ($this->region && $this->district) {
            if (!isset($this->addresses[$this->region][$this->district])) {
                return false;
            }
            return in_array($value, $this->addresses[$this->region][$this->district]);
        }

        // If only region is provided, check if township exists in any district of that region
        if ($this->region) {
            if (!isset($this->addresses[$this->region])) {
                return false;
            }
            foreach ($this->addresses[$this->region] as $districts) {
                if (in_array($value, $districts)) {
                    return true;
                }
            }
            return false;
        }

        // If no region/district provided, check if township exists anywhere
        foreach ($this->addresses as $region => $districts) {
            foreach ($districts as $townships) {
                if (in_array($value, $townships)) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Get the validation error message.
     */
    public function message(): string
    {
        return 'The selected :attribute is not a valid Myanmar township.';
    }
}

