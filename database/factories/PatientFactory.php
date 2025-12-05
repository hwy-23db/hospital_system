<?php

namespace Database\Factories;

use App\Models\Patient;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Patient>
 */
class PatientFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     */
    protected $model = Patient::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'nrc_number' => $this->generateNRC(),
            'sex' => fake()->randomElement(['male', 'female']),
            'age' => fake()->numberBetween(1, 100),
            'dob' => fake()->date(),
            'contact_phone' => '09' . fake()->numerify('#########'),
            'permanent_address' => fake()->address(),
            'marital_status' => fake()->randomElement(['single', 'married', 'divorced', 'widowed']),
            'ethnic_group' => fake()->randomElement(['Bamar', 'Shan', 'Karen', 'Kachin', 'Chin', 'Mon', 'Rakhine']),
            'religion' => fake()->randomElement(['Buddhist', 'Christian', 'Muslim', 'Hindu', 'Other']),
            'occupation' => fake()->jobTitle(),
            'father_name' => 'U ' . fake()->firstName('male'),
            'mother_name' => 'Daw ' . fake()->firstName('female'),
            'nearest_relative_name' => fake()->name(),
            'nearest_relative_phone' => '09' . fake()->numerify('#########'),
            'relationship' => fake()->randomElement(['spouse', 'parent', 'sibling', 'child', 'other']),
            'blood_type' => fake()->randomElement(['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-']),
            'known_allergies' => fake()->optional()->sentence(),
            'chronic_conditions' => fake()->optional()->sentence(),
        ];
    }

    /**
     * Generate a Myanmar NRC number format
     */
    private function generateNRC(): string
    {
        $states = ['1', '2', '3', '4', '5', '6', '7', '8', '9', '10', '11', '12', '13', '14'];
        $townships = ['ABC', 'XYZ', 'PQR', 'LMN', 'DEF'];
        $types = ['N', 'P', 'E', 'T'];

        $state = fake()->randomElement($states);
        $township = fake()->randomElement($townships);
        $type = fake()->randomElement($types);
        $number = fake()->numerify('######');

        return "{$state}/{$township}({$type}){$number}";
    }
}
