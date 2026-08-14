<?php

namespace Database\Seeders;

use App\Models\OfficeIpAllowlist;
use Illuminate\Database\Seeder;

class OfficeIpAllowlistSeeder extends Seeder
{
    public function run(): void
    {
        OfficeIpAllowlist::firstOrCreate(
            ['ip_address' => '127.0.0.1'],
            [
                'description' => 'Localhost Development Loopback (IPv4)',
                'is_active' => true,
            ]
        );

        OfficeIpAllowlist::firstOrCreate(
            ['ip_address' => '::1'],
            [
                'description' => 'Localhost Development Loopback (IPv6)',
                'is_active' => true,
            ]
        );
    }
}
