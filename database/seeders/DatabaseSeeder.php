<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Order matters: readers can only be given progress once the books and
        // their quizzes exist, and badges must exist before progress is scored
        // so the automatic awards fire.
        $this->call([
            ContentSeeder::class,
            BadgeSeeder::class,
            AnnouncementSeeder::class,
            SlideSeeder::class,
            UserSeeder::class,
        ]);
    }
}
