<?php

namespace App\Helper;

use App\Models\User;

class ProfileHelper
{
    public static function formatUserProfile(User $user): array
    {
        return [
            'user' => self::formatUserData($user),
            'address' => self::formatAddress($user),
            'contact' => self::formatContact($user),
            'listings' => self::formatListings($user)
        ];
    }

    protected static function formatUserData(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => self::formatRole($user->role),
            'status' => $user->status == '0' ? 'Inactive' : 'Active',
            'join_date' => $user->created_at->format('M d, Y'),
        ];
    }

    protected static function formatAddress(User $user): ?array
    {
        if (!$user->relationLoaded('address') || !$user->address) {
            return null;
        }

        return [
            'city' => $user->address->city,
            'district' => $user->address->district,
            'full_address' => "{$user->address->city}, {$user->address->district}"
        ];
    }

    protected static function formatContact(User $user): ?array
    {
        if (!$user->relationLoaded('contact') || $user->contact->isEmpty()) {
            return null;
        }

        $contact = $user->contact->first();
        return [
            'phone' => self::formatPhone($contact->phone_no),
            'telegram' => $contact->username
        ];
    }

    protected static function formatListings(User $user): array
    {
        return [
            'real_estate' => self::formatRealEstate($user),
            'services' => self::formatServices($user)
        ];
    }

    public static function formatRealEstate(User $user): array
    {
        if (!$user->relationLoaded('realEstate')) {
            return [];
        }

        return $user->realEstate->map(function ($property) {
            return [
                'id' => $property->id,
                'type' => ucfirst($property->type),
                'price' => $property->price > 0
                    ? '$' . number_format($property->price)
                    : 'Contact for price',
                'status' => $property->status,
                'location' => $item->location ? [
                    'city' => $item->location->city,
                    'district' => $item->location->district,
                ] : null,
                'images' => $property->images->map(function ($image) {
                    return ['name' => $image->name, 'type' => $image->type];
                }),
                'details' => [
                    'kind' => $property->kind,
                    // 'room_no' => $properties->properties->room_no,
                    'description' => $property->description ?: 'No description'
                ]
            ];
        })->toArray();

    }

    protected static function formatServices(User $user): array
    {


        return $user->service->map(function ($property) {
            return [
                'id' => $property->id,
                'title' => ucfirst($property->title),
                'description' => ucfirst($property->description),

                'services_type_id' => $property->services_type_id,
                'user_id' => $property->user_id,

            ];
        })->toArray();
    }

    protected static function formatPhone(string|int $phone): string
    {
        $phone = (string)$phone;
        return '+963 ' . substr($phone, 0, 3) . ' ' . substr($phone, 3, 3) . ' ' . substr($phone, 6);
    }

    protected static function formatRole(string $role): string
    {
        return match ($role) {
            '0' => 'Regular User',
            '1' => 'Admin',
            '2' => 'Agent',
            default => 'Unknown Role'
        };
    }
}
