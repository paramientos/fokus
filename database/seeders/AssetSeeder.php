<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\AssetCategory;
use App\Models\Asset;
use App\Models\SoftwareLicense;
use App\Models\Workspace;
use App\Models\User;

class AssetSeeder extends Seeder
{
    public function run(): void
    {
        $workspace = Workspace::first();
        $user = User::first();

        if (!$workspace || !$user) {
            return;
        }

        // Asset Categories
        $categories = [
            [
                'name' => 'Laptops',
                'slug' => 'laptops',
                'description' => 'Company laptops and notebooks',
                'color' => '#3b82f6',
                'icon' => 'fas.laptop',
            ],
            [
                'name' => 'Monitors',
                'slug' => 'monitors',
                'description' => 'External monitors and displays',
                'color' => '#10b981',
                'icon' => 'fas.desktop',
            ],
            [
                'name' => 'Mobile Devices',
                'slug' => 'mobile-devices',
                'description' => 'Smartphones and tablets',
                'color' => '#f59e0b',
                'icon' => 'fas.mobile-alt',
            ],
            [
                'name' => 'Office Equipment',
                'slug' => 'office-equipment',
                'description' => 'Printers, scanners, and other office equipment',
                'color' => '#8b5cf6',
                'icon' => 'fas.print',
            ],
            [
                'name' => 'Furniture',
                'slug' => 'furniture',
                'description' => 'Desks, chairs, and office furniture',
                'color' => '#ef4444',
                'icon' => 'fas.chair',
            ],
        ];

        foreach ($categories as $categoryData) {
            AssetCategory::create([
                'workspace_id' => $workspace->id,
                ...$categoryData,
            ]);
        }

        // Sample Assets
        $laptopCategory = AssetCategory::where('slug', 'laptops')->first();
        $monitorCategory = AssetCategory::where('slug', 'monitors')->first();
        $mobileCategory = AssetCategory::where('slug', 'mobile-devices')->first();

        $assets = [
            [
                'asset_category_id' => $laptopCategory->id,
                'name' => 'MacBook Pro 16"',
                'brand' => 'Apple',
                'model' => 'MacBook Pro',
                'serial_number' => 'C02XJ0AAJGH5',
                'purchase_price' => 2499.00,
                'purchase_date' => now()->subMonths(6),
                'warranty_expiry' => now()->addMonths(30),
                'location' => 'Office',
                'room' => 'Dev Team',
                'status' => 'assigned',
                'assigned_to' => $user->id,
            ],
            [
                'asset_category_id' => $laptopCategory->id,
                'name' => 'Dell XPS 13',
                'brand' => 'Dell',
                'model' => 'XPS 13',
                'serial_number' => 'DXPS13001',
                'purchase_price' => 1299.00,
                'purchase_date' => now()->subMonths(3),
                'warranty_expiry' => now()->addMonths(21),
                'location' => 'Office',
                'room' => 'Design Team',
                'status' => 'available',
            ],
            [
                'asset_category_id' => $monitorCategory->id,
                'name' => 'LG UltraWide 34"',
                'brand' => 'LG',
                'model' => '34WN80C-B',
                'serial_number' => 'LG34001',
                'purchase_price' => 399.00,
                'purchase_date' => now()->subMonths(4),
                'warranty_expiry' => now()->addMonths(20),
                'location' => 'Office',
                'room' => 'Dev Team',
                'status' => 'assigned',
                'assigned_to' => $user->id,
            ],
            [
                'asset_category_id' => $mobileCategory->id,
                'name' => 'iPhone 15 Pro',
                'brand' => 'Apple',
                'model' => 'iPhone 15 Pro',
                'serial_number' => 'IP15PRO001',
                'purchase_price' => 999.00,
                'purchase_date' => now()->subMonths(2),
                'warranty_expiry' => now()->addMonths(10),
                'location' => 'Remote',
                'status' => 'assigned',
                'assigned_to' => $user->id,
            ],
        ];

        foreach ($assets as $assetData) {
            Asset::create([
                'workspace_id' => $workspace->id,
                'created_by' => $user->id,
                ...$assetData,
            ]);
        }

        // Software Licenses
        $licenses = [
            [
                'name' => 'Adobe Creative Cloud',
                'vendor' => 'Adobe',
                'version' => '2024',
                'license_type' => 'subscription',
                'purchase_date' => now()->subMonths(6),
                'expiry_date' => now()->addMonths(6),
                'cost' => 52.99,
                'billing_cycle' => 'monthly',
                'total_licenses' => 10,
                'used_licenses' => 3,
                'description' => 'Complete creative suite for design team',
                'status' => 'active',
                'auto_renewal' => true,
            ],
            [
                'name' => 'Microsoft Office 365',
                'vendor' => 'Microsoft',
                'version' => '2024',
                'license_type' => 'subscription',
                'purchase_date' => now()->subYear(),
                'expiry_date' => now()->addMonths(2),
                'cost' => 12.50,
                'billing_cycle' => 'monthly',
                'total_licenses' => 50,
                'used_licenses' => 25,
                'description' => 'Office productivity suite',
                'status' => 'active',
                'auto_renewal' => true,
            ],
            [
                'name' => 'Slack Pro',
                'vendor' => 'Slack Technologies',
                'license_type' => 'subscription',
                'purchase_date' => now()->subMonths(8),
                'expiry_date' => now()->addMonths(4),
                'cost' => 7.25,
                'billing_cycle' => 'monthly',
                'total_licenses' => 30,
                'used_licenses' => 18,
                'description' => 'Team communication platform',
                'status' => 'active',
                'auto_renewal' => true,
            ],
            [
                'name' => 'JetBrains IntelliJ IDEA',
                'vendor' => 'JetBrains',
                'license_type' => 'subscription',
                'purchase_date' => now()->subMonths(10),
                'expiry_date' => now()->addMonths(2),
                'cost' => 149.00,
                'billing_cycle' => 'yearly',
                'total_licenses' => 5,
                'used_licenses' => 4,
                'description' => 'IDE for Java development',
                'status' => 'active',
                'auto_renewal' => false,
            ],
        ];

        foreach ($licenses as $licenseData) {
            SoftwareLicense::create([
                'workspace_id' => $workspace->id,
                'created_by' => $user->id,
                ...$licenseData,
            ]);
        }
    }
}
