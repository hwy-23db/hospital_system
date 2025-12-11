<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class MyanmarAddress implements Rule
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
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        if (empty($value)) {
            return true; // Allow null/empty addresses
        }

        // If value is a string, try to parse it as JSON
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                $value = $decoded;
            } else {
                // If not JSON, it's a plain string - validate it's a valid address format
                // For backward compatibility, we'll allow plain strings but prefer structured data
                return true;
            }
        }

        // If value is an array, validate the structure
        if (is_array($value)) {
            // Check if it has the required structure: region, district, township
            if (!isset($value['region']) || !isset($value['district']) || !isset($value['township'])) {
                return false;
            }

            $region = $value['region'];
            $district = $value['district'];
            $township = $value['township'];

            // Validate region exists
            if (!isset($this->addresses[$region])) {
                return false;
            }

            // Validate district exists in region
            if (!isset($this->addresses[$region][$district])) {
                return false;
            }

            // Validate township exists in district
            if (!in_array($township, $this->addresses[$region][$district])) {
                return false;
            }

            return true;
        }

        // If it's neither string nor array, fail validation
        return false;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return 'The :attribute must be a valid Myanmar address. Provide a JSON object with "region", "district", and "township" fields that match the Myanmar addresses list.';
    }
}
