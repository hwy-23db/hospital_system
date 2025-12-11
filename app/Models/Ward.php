<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ward extends Model
{
    use HasFactory;

    /**
     * Get hospital wards with their associated room numbers.
     * Organized by ward for hierarchical selection in admission forms.
     */
    public static function getWardsWithRooms(): array
    {
        return [
            'oncology_ward_a' => [
                'name' => 'Oncology Ward A',
                'rooms' => ['101', '102', '103', '104', '105', '106', '107', '108', '109', '110']
            ],
            'oncology_ward_b' => [
                'name' => 'Oncology Ward B',
                'rooms' => ['201', '202', '203', '204', '205', '206', '207', '208', '209', '210']
            ],
            'surgical_ward' => [
                'name' => 'Surgical Ward',
                'rooms' => ['301', '302', '303', '304', '305', '306', '307', '308']
            ],
            'icu_oncology' => [
                'name' => 'Oncology ICU',
                'rooms' => ['401', '402', '403', '404', '405', '406']
            ],
            'palliative_care_ward' => [
                'name' => 'Palliative Care Ward',
                'rooms' => ['501', '502', '503', '504', '505', '506']
            ],
            'pediatric_oncology' => [
                'name' => 'Pediatric Oncology Ward',
                'rooms' => ['601', '602', '603', '604', '605']
            ],
            'day_care_unit' => [
                'name' => 'Day Care Unit',
                'rooms' => ['701', '702', '703', '704', '705', '706', '707', '708', '709', '710']
            ],
            'isolation_ward' => [
                'name' => 'Isolation Ward',
                'rooms' => ['801', '802', '803', '804']
            ]
        ];
    }

    /**
     * Get all ward keys for validation.
     */
    public static function getWardKeys(): array
    {
        return array_keys(self::getWardsWithRooms());
    }

    /**
     * Get rooms for a specific ward.
     */
    public static function getRoomsForWard(string $wardKey): array
    {
        $wards = self::getWardsWithRooms();
        return $wards[$wardKey]['rooms'] ?? [];
    }

    /**
     * Check if a ward key exists.
     */
    public static function wardExists(string $wardKey): bool
    {
        return isset(self::getWardsWithRooms()[$wardKey]);
    }

    /**
     * Check if a room number is valid for a given ward.
     */
    public static function isValidRoomForWard(string $wardKey, string $roomNumber): bool
    {
        $rooms = self::getRoomsForWard($wardKey);
        return in_array($roomNumber, $rooms);
    }
}
