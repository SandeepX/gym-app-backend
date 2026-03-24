<?php

namespace Database\Seeders;

use App\Enums\PlanTypeEnum;
use App\Models\Plan;
use Illuminate\Database\Seeder;

class PlanSeeder extends Seeder
{
    public function run(): void
    {
        $plans = [
            [
                'name' => 'Monthly Basic',
                'description' => 'Perfect for beginners. Access to all basic gym equipment and facilities.',
                'price' => 199.99,
                'duration_days' => 30,
                'max_freeze_days' => 5,
                'type' => PlanTypeEnum::Monthly->value,
                'features' => [
                    'Gym Access',
                    'Locker Room',
                    'Free WiFi',
                ],
                'is_active' => true,
            ],
            [
                'name' => 'Monthly Premium',
                'description' => 'Full access to all gym facilities including classes and personal trainer sessions.',
                'price' => 349.99,
                'duration_days' => 30,
                'max_freeze_days' => 7,
                'type' => PlanTypeEnum::Monthly->value,
                'features' => [
                    'Gym Access',
                    'Locker Room',
                    'Free WiFi',
                    'Group Classes',
                    '2 PT Sessions',
                    'Sauna Access',
                ],
                'is_active' => true,
            ],
            [
                'name' => 'Quarterly Basic',
                'description' => '3 months of gym access at a discounted rate.',
                'price' => 549.99,
                'duration_days' => 90,
                'max_freeze_days' => 10,
                'type' => PlanTypeEnum::Quarterly->value,
                'features' => [
                    'Gym Access',
                    'Locker Room',
                    'Free WiFi',
                ],
                'is_active' => true,
            ],
            [
                'name' => 'Quarterly Premium',
                'description' => '3 months full access with classes and personal trainer sessions.',
                'price' => 999.99,
                'duration_days' => 90,
                'max_freeze_days' => 14,
                'type' => PlanTypeEnum::Quarterly->value,
                'features' => [
                    'Gym Access',
                    'Locker Room',
                    'Free WiFi',
                    'Group Classes',
                    '6 PT Sessions',
                    'Sauna Access',
                    'Nutrition Consultation',
                ],
                'is_active' => true,
            ],
            [
                'name' => 'Half Yearly',
                'description' => '6 months of full gym access at the best value.',
                'price' => 1799.99,
                'duration_days' => 180,
                'max_freeze_days' => 21,
                'type' => PlanTypeEnum::HalfYearly->value,
                'features' => [
                    'Gym Access',
                    'Locker Room',
                    'Free WiFi',
                    'Group Classes',
                    '12 PT Sessions',
                    'Sauna Access',
                    'Nutrition Consultation',
                    'Body Assessment',
                ],
                'is_active' => true,
            ],
            [
                'name' => 'Annual',
                'description' => 'Full year membership with maximum benefits and savings.',
                'price' => 2999.99,
                'duration_days' => 365,
                'max_freeze_days' => 30,
                'type' => PlanTypeEnum::Yearly->value,
                'features' => [
                    'Gym Access',
                    'Locker Room',
                    'Free WiFi',
                    'Group Classes',
                    'Unlimited PT Sessions',
                    'Sauna Access',
                    'Nutrition Consultation',
                    'Body Assessment',
                    'Guest Passes x12',
                    'Priority Booking',
                ],
                'is_active' => true,
            ],
            [
                'name' => 'Student Plan',
                'description' => 'Special discounted plan for students with valid student ID.',
                'price' => 149.99,
                'duration_days' => 30,
                'max_freeze_days' => 5,
                'type' => PlanTypeEnum::Monthly->value,
                'features' => [
                    'Gym Access',
                    'Locker Room',
                    'Free WiFi',
                    'Student Discount',
                ],
                'is_active' => true,
            ],
            [
                'name' => 'Day Pass',
                'description' => 'Single day access to all gym facilities.',
                'price' => 49.99,
                'duration_days' => 1,
                'max_freeze_days' => 0,
                'type' => PlanTypeEnum::Custom->value,
                'features' => [
                    'Gym Access',
                    'Locker Room',
                ],
                'is_active' => true,
            ],
        ];

        foreach ($plans as $plan) {
            Plan::firstOrCreate(
                ['name' => $plan['name']],
                $plan
            );
        }

        $this->command->info('✅ Plans seeded successfully!');
        $this->command->table(
            ['Name', 'Type', 'Price', 'Duration', 'Freeze Days'],
            Plan::all()->map(fn ($p) => [
                $p->name,
                $p->type->value,
                $p->price.' EGP',
                $p->duration_days.' days',
                $p->max_freeze_days.' days',
            ])->toArray()
        );
    }
}
