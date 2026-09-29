<?php

namespace Database\Seeders;

use App\Models\AdminUser;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        // 1. Create Default Admin User
        AdminUser::updateOrCreate(
            ['username' => 'admin'],
            [
                'name' => 'System Administrator',
                'email' => 'admin@worklink.com',
                'password' => Hash::make('AdminPass123!'),
                'role' => 'super_admin',
                'is_active' => true,
            ]
        );

        // 2. Seed 10 Categories and Extensive Skills
        $this->call(CategoryAndSkillSeeder::class);

        // 3. Seed 10 Customers and 15 Skilled Workers with full Profiles
        $this->call(DevelopmentUserSeeder::class);

        // 4. Seed Worker Portfolio records
        $this->call(DevelopmentPortfolioSeeder::class);

        // 5. Seed Realistic Jobs across all 7 statuses with locations & history
        $this->call(DevelopmentJobSeeder::class);

        // 6. Seed Chats and Messages
        $this->call(DevelopmentChatSeeder::class);

        // 7. Seed Reviews & Calculate Worker Ratings
        $this->call(DevelopmentReviewSeeder::class);

        // 8. Seed Notifications
        $this->call(DevelopmentNotificationSeeder::class);

        // 9. Seed Payments and Transactions (Phase 11)
        $this->call(DevelopmentPaymentSeeder::class);
    }
}
