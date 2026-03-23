<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UsersSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::updateOrCreate(
            [
                'email'             => env('BOB1_USER_EMAIL'),
                'name'              => 'Bob Bloom',
                'password'          => Hash::make(env('BOB1_USER_PWD', 'password')), // Always hash your passwords!
                'email_verified_at' => now(),
            ]
        );

        User::updateOrCreate(
            [
                'email'             => env('BOB2_USER_EMAIL'),
                'name'              => 'Bob Bloom @ me',
                'password'          => Hash::make(env('BOB2_USER_PWD', 'password')), // Always hash your passwords!
                'email_verified_at' => now(),
            ]
        );
    }
}
