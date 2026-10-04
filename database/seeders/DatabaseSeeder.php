<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(LeaveTypeSeeder::class);
        $this->call(RolesAndPermissionsSeeder::class);
        $this->call(TestInitialDataSeeder::class);
        $this->call(RolesAndPermissionsSeeder::class);

        // ApiTestingSeeder is intentionally NOT called here automatically.
        // Run it manually when needed:
        //   php artisan db:seed --class=ApiTestingSeeder
    }
}
