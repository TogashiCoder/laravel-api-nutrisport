<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AgentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Agent ID=1 has full access (no permission checks per PRD).
     */
    public function run(): void
    {
        DB::table('agents')->updateOrInsert(
            ['id' => 1],
            [
                'name' => 'Admin Agent',
                'email' => 'agent@nutrisport.com',
                'password' => bcrypt('password'),
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );
    }
}
