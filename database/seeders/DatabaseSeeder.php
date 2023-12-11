<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;

use App\Models\Client;
use App\Models\Member;
use App\Models\Organization;
use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Role;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        //Roles
        $sysadmin = new Role();
        $sysadmin->name = 'SuperAdmin';
        $sysadmin->save();
        $admin = new Role();
        $admin->name = 'Admin';
        $admin->save();
        $student = new Role();
        $student->name = 'Student';
        $student->save();


        //Ximdex
        $user = new User();
        $user->email = 'admin@ximdex.com';
        $user->password = Hash::make('1234');
        $user->save();

        $client = new Client();
        $client->name = 'Ximdex';
        $client->user_id = $user->id;
        $client->save();
        $client->roles()->attach($sysadmin->id);

        $org = new Organization();
        $org->name = 'XIMDEX';
        $org->client_id = $client->id;
        $org->description = 'Ximdex es una empresa sevillana de tecnologías de la información y comunicaciones especializada en el desarrollo de sistemas de gestión de contenidos. Fue fundada en el año 2010 con foco en el software libre y el objetivo de crear un sistema de gestión de contenidos semántico con el mismo nombre de la compañía.';
        $org->save();

        $org = new Organization();
        $org->name = 'Public';
        $org->client_id = $client->id;
        $org->description = 'Organizacion por defecto';
        $org->save();
    }
}
