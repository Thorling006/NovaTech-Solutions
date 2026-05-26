<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $roleSuper = \App\Models\Role::create(['nombre' => 'Superusuario']);
        $roleAdmin = \App\Models\Role::create(['nombre' => 'Administrador']);
        $roleEmpleado = \App\Models\Role::create(['nombre' => 'Empleado']);
        $roleConductor = \App\Models\Role::create(['nombre' => 'Conductor']);

        User::create([
            'name' => 'Super Admin',
            'email' => 'admin@novastock.test',
            'password' => bcrypt('password'), // password is 'password'
            'role_id' => $roleSuper->id,
        ]);
    }
}
