<?php

namespace Database\Seeders;

use App\Models\DocumentType;
use Illuminate\Database\Seeder;

class DocumentTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            ['slug' => 'identity_proof', 'name' => 'Identity Proof (Aadhaar / Passport / Voter ID)', 'is_mandatory' => true],
            ['slug' => 'degree_certificate', 'name' => 'Highest Degree / Educational Certificate', 'is_mandatory' => false],
            ['slug' => 'offer_letter', 'name' => 'Signed Offer / Appointment Letter', 'is_mandatory' => true],
            ['slug' => 'bank_passbook', 'name' => 'Bank Passbook / Cancelled Cheque', 'is_mandatory' => true],
            ['slug' => 'medical_certificate', 'name' => 'Medical Certificate (For Medical Leaves)', 'is_mandatory' => false],
        ];

        foreach ($types as $type) {
            DocumentType::firstOrCreate(['slug' => $type['slug']], $type);
        }
    }
}
