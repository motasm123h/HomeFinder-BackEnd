<?php

namespace App\Helper;

use App\Models\User;

class OfficeHelper
{
    public static function formatOffice(User $user): array
    {
        $address = $user->address;
        $contact = $user->contact->first();

        return [
            'id' => $user->id, // Arabic name (if stored)
            'name' => $user->name, // Arabic name (if stored)
            'address' => $address ? "{$address->city} - {$address->district}" : 'N/A',
            'phone' => $contact ? '+963 '.substr($contact->phone_no, 0, 3).' '.substr($contact->phone_no, 3, 3).' '.substr($contact->phone_no, 6) : 'N/A',
            'whatsapp' => $contact ? '963'.$contact->phone_no : 'N/A',
            'telegram' => $contact ? $contact->username : 'N/A',
        ];
    }
}
