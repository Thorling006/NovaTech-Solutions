<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Conductor;
use App\Models\User;
use App\Models\Role;
use Illuminate\Support\Facades\Hash;

class ConductorSeeder extends Seeder
{
    public function run(): void
    {
        $roleConductor = Role::firstOrCreate(['nombre' => 'Conductor']);

        $conductores = [
            [
                'nombre' => 'Carlos Martínez',
                'email' => 'carlos@conductor.test',
                'vehiculo_tipo' => 'moto',
            ],
            [
                'nombre' => 'Luis Ernesto',
                'email' => 'luis@conductor.test',
                'vehiculo_tipo' => 'carro',
            ],
            [
                'nombre' => 'Juan Pérez',
                'email' => 'juan@conductor.test',
                'vehiculo_tipo' => 'camion',
            ],
        ];

        foreach ($conductores as $conductorInfo) {
            // Create user
            $user = User::firstOrCreate(
                ['email' => $conductorInfo['email']],
                [
                    'name' => $conductorInfo['nombre'],
                    'password' => Hash::make('password'),
                    'role_id' => $roleConductor->id,
                ]
            );

            // Create conductor profile
            Conductor::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'nombre' => $conductorInfo['nombre'],
                    'foto_url' => 'https://ui-avatars.com/api/?name=' . urlencode($conductorInfo['nombre']) . '&background=random',
                    'estado' => 'disponible',
                    'vehiculo_tipo' => $conductorInfo['vehiculo_tipo'],
                ]
            );
        }
    }
}
