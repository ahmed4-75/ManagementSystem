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

        Role::whereNotIn('name', $enumRoles)->delete();

        $roles = collect($enumRoles)->map(fn($role) => ['name' => $role,'updated_at' => now()])->toArray();

        Role::upsert($roles, ['name'],['updated_at']);
    }
}
