<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SiteSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $sites = [
            ['code' => 'fr', 'domain' => 'nutri-sport.fr', 'currency' => 'EUR'],
            ['code' => 'it', 'domain' => 'nutri-sport.it', 'currency' => 'EUR'],
            ['code' => 'be', 'domain' => 'nutri-sport.be', 'currency' => 'EUR'],
        ];

        foreach ($sites as $site) {
            DB::table('sites')->updateOrInsert(
                ['code' => $site['code']],
                array_merge($site, [
                    'created_at' => now(),
                    'updated_at' => now(),
                ])
            );
        }
    }
}
