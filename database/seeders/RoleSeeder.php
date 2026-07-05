<?php

namespace Database\Seeders;

use App\Enums\RolesEnum;
use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $enumRoles = RolesEnum::values();

        // ✅ 1. حذف ما ليس في Enum و فك الارتباط بين الـ Roles و الـ Users
        Role::whereNotIn('name', $enumRoles)->delete();

        // ✅ 2. إضافة/تحديث ما في Enum
        $roles = collect($enumRoles)->map(fn($role) => ['name' => $role,'updated_at' => now()])->toArray();

        Role::upsert($roles, ['name'],['updated_at']);
    }
}
