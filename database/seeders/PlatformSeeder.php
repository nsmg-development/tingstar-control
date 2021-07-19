<?php

namespace Database\Seeders;

use App\Models\Platform;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PlatformSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $data = [
            ['platform' => 'instagram'],
            ['platform' => 'facebook'],
            ['platform' => 'tiktok'],
            ['platform' => 'youtube'],
            ['platform' => 'naver-blog'],
            ['platform' => 'twitter'],
        ];

        foreach ($data as $item) {
            $platform = Platform::create([
                'platform' => $item['platform'],
            ]);
        }
    }
}
