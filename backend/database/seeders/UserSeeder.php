<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = [
            [
                'name' => 'user',
                'email' => 'user@email.com',
                'password' => Hash::make('password'),
                'role' => 'user'
            ],
            [
                'name' => 'user2',
                'email' => 'user2@email.com',
                'password' => Hash::make('password'),
                'role' => 'user'
            ],
            [
                'name' => 'agent',
                'email' => 'agent@email.com',
                'password' => Hash::make('password'),
                'role' => 'agent'
            ],
            [
                'name' => 'agent2',
                'email' => 'agent2@email.com',
                'password' => Hash::make('password'),
                'role' => 'agent'
            ],
            [
                'name' => 'admin',
                'email' => 'admin@email.com',
                'password' => Hash::make('password'),
                'role' => 'admin'
            ],
        ];

        foreach ($users as $user) {
            User::create($user);
        }
    }
}
