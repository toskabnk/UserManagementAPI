<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;

use App\Models\Organization;
use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Role;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::factory(10)->create();

        $role = new Role();
        $role->name = 'Admin';
        $role->save();
        $role = new Role();
        $role->name = 'User';
        $role->save();
        $role = new Role();
        $role->name = 'Guest';
        $role->save();

        $org = new Organization();
        $org->name = 'XIMDEX';
        $org->description = 'Ximdex es una empresa sevillana de tecnologías de la información y comunicaciones especializada en el desarrollo de sistemas de gestión de contenidos. Fue fundada en el año 2010 con foco en el software libre y el objetivo de crear un sistema de gestión de contenidos semántico con el mismo nombre de la compañía.';
        $org->save();
        $org = new Organization();
        $org->name = 'San Valero';
        $org->description = 'Centro San Valero es un centro de enseñanza concertada y privada. Imparte formación de ESO, Bachillerato, FP Básica, Grado Medio, Grado Superior, Cursos de especialización, cursos de formación para el empleo, entre otras. La Fundación San Valero es una Obra Diocesana de carácter no lucrativo al servicio de las personas y de la sociedad.';
        $org->save();

        // \App\Models\User::factory()->create([
        //     'name' => 'Test User',
        //     'email' => 'test@example.com',
        // ]);
    }
}
