<?php

namespace Database\Seeders;

use App\Models\Subacquirer;
use App\Models\User;
use Illuminate\Database\Seeder;

class GeneralSeeder extends Seeder
{
    public function run(): void
    {
        Subacquirer::updateOrCreate(['slug' => 'subadqa'], [
            'name' => 'SubadqA',
            'base_url' => config('subacquirers.defaults.subadqa.base_url'),
            'config' => json_encode([]),
        ]);

        Subacquirer::updateOrCreate(['slug' => 'subadqb'], [
            'name' => 'SubadqB',
            'base_url' => config('subacquirers.defaults.subadqb.base_url'),
            'config' => json_encode([]),
        ]);

        User::factory()->create([
            'subacquirer_id' => Subacquirer::first()->id,
        ]);
    }
}
