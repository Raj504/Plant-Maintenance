<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * php artisan migrate:fresh --seed
     *
     * Order matters: permissions & roles -> master data -> users -> demo data.
     * Set SEED_DEMO_DATA=false in .env to skip the demo factory data on a real installation.
     */
    public function run(): void
    {
        $this->call([
            RolePermissionSeeder::class,
            MasterDataSeeder::class,
            UserSeeder::class,
        ]);

        if (env('SEED_DEMO_DATA', true)) {
            $this->call(DemoDataSeeder::class);
        }
    }
}
