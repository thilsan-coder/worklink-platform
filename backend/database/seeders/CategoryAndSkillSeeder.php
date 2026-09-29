<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Skill;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CategoryAndSkillSeeder extends Seeder
{
    public function run(): void
    {
        $categoriesData = [
            [
                'name' => 'Plumbing Services',
                'slug' => 'plumbing-services',
                'icon' => 'plumbing',
                'description' => 'Pipe leak repair, fixture installation, drain cleaning, and water tank maintenance.',
                'sort_order' => 1,
                'skills' => [
                    'Leak Detection & Pipe Repair',
                    'Faucet & Tap Fitting',
                    'Drainage & Sewer Unclogging',
                    'Water Heater Repair & Installation',
                    'Bathroom Fixture Installation',
                ],
            ],
            [
                'name' => 'Electrical Services',
                'slug' => 'electrical-services',
                'icon' => 'bolt',
                'description' => 'Wiring, circuit breaker repair, lighting, and switchboard installation.',
                'sort_order' => 2,
                'skills' => [
                    'Wiring & Full Circuit Rewiring',
                    'Switchboard & Socket Installation',
                    'Ceiling Fan & Light Fixture Fitting',
                    'Short Circuit Diagnosis & Fuse Repair',
                    'Inverter & Backup Battery Setup',
                ],
            ],
            [
                'name' => 'Carpentry & Furniture',
                'slug' => 'carpentry-furniture',
                'icon' => 'carpentry',
                'description' => 'Custom woodworking, door lock fitting, furniture repair, and cabinet installation.',
                'sort_order' => 3,
                'skills' => [
                    'Custom Woodworking & Shelving',
                    'Door Lock & Hinge Fitting',
                    'Modular Cabinet Assembly',
                    'Furniture Restoration & Polish',
                ],
            ],
            [
                'name' => 'AC & Appliance Repair',
                'slug' => 'ac-appliance-repair',
                'icon' => 'ac_unit',
                'description' => 'Air conditioner servicing, gas refilling, refrigerator, and washing machine repair.',
                'sort_order' => 4,
                'skills' => [
                    'Split & Window AC Servicing',
                    'AC Gas Refill & Leak Testing',
                    'Washing Machine Repair',
                    'Refrigerator & Freezer Repair',
                    'Microwave & Oven Repair',
                ],
            ],
            [
                'name' => 'Painting & Waterproofing',
                'slug' => 'painting-waterproofing',
                'icon' => 'format_paint',
                'description' => 'Interior and exterior wall painting, texture finish, and roof waterproofing.',
                'sort_order' => 5,
                'skills' => [
                    'Interior Wall Painting',
                    'Exterior Wall Weatherproofing',
                    'Roof & Wall Damp Proofing',
                    'Texture & Accent Wall Finish',
                ],
            ],
            [
                'name' => 'Cleaning & Sanitization',
                'slug' => 'cleaning-sanitization',
                'icon' => 'cleaning_services',
                'description' => 'Deep house cleaning, sofa shampooing, water tank cleaning, and pest control.',
                'sort_order' => 6,
                'skills' => [
                    'Full Home Deep Cleaning',
                    'Sofa & Carpet Shampooing',
                    'Water Tank Cleaning',
                    'Pest Control & Fumigation',
                ],
            ],
            [
                'name' => 'Computer & Laptop Repair',
                'slug' => 'computer-laptop-repair',
                'icon' => 'computer',
                'description' => 'Hardware diagnostic, screen replacement, OS installation, and virus removal.',
                'sort_order' => 7,
                'skills' => [
                    'Hardware Troubleshooting & Repair',
                    'Laptop Screen & Battery Replacement',
                    'OS & Software Installation',
                    'Data Recovery & Virus Cleaning',
                ],
            ],
            [
                'name' => 'Web & Software Development',
                'slug' => 'web-software-development',
                'icon' => 'code',
                'description' => 'Custom websites, mobile apps, e-commerce stores, and API integrations.',
                'sort_order' => 8,
                'skills' => [
                    'Custom Website Development',
                    'WordPress & E-Commerce Stores',
                    'Mobile Application Development',
                    'API & Database Development',
                ],
            ],
            [
                'name' => 'Graphic & UI Design',
                'slug' => 'graphic-ui-design',
                'icon' => 'palette',
                'description' => 'Brand logos, marketing flyers, social media kits, and mobile UI designs.',
                'sort_order' => 9,
                'skills' => [
                    'Logo & Brand Identity',
                    'Brochures, Flyers & Banners',
                    'Social Media Post Design',
                    'UI/UX Mobile & Web Wireframing',
                ],
            ],
            [
                'name' => 'Mobile Phone Repair',
                'slug' => 'mobile-phone-repair',
                'icon' => 'smartphone',
                'description' => 'Display glass replacement, battery swap, charging port fix, and water damage recovery.',
                'sort_order' => 10,
                'skills' => [
                    'Screen & OLED Display Replacement',
                    'Battery Swap & Charging Port Fix',
                    'Water Damage Diagnostic & Board Repair',
                    'Camera & Speaker Replacement',
                ],
            ],
        ];

        foreach ($categoriesData as $catItem) {
            $skills = $catItem['skills'];
            unset($catItem['skills']);

            $category = Category::updateOrCreate(
                ['slug' => $catItem['slug']],
                $catItem
            );

            foreach ($skills as $skillName) {
                Skill::updateOrCreate(
                    ['slug' => Str::slug($skillName)],
                    [
                        'category_id' => $category->id,
                        'name' => $skillName,
                        'description' => $skillName . ' professional service.',
                        'is_active' => true,
                    ]
                );
            }
        }
    }
}
