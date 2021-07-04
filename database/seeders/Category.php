<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Utils\FuncRandom;

class Category extends Seeder
{
    use FuncRandom;
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('category')
            ->insert([
                [
                    'id' => $this->uuid_short(),
                    'category_name' => 'Olahraga',
                    'category_url' => 'olahraga',
                    'active' => 1,
                    'uuid' => $this->uuid()
                ],
                [
                    'id' => $this->uuid_short(),
                    'category_name' => 'Pendidikan',
                    'category_url' => 'pendidikan',
                    'active' => 0,
                    'uuid' => $this->uuid()
                ],
                [
                    'id' => $this->uuid_short(),
                    'category_name' => 'Kesehatan',
                    'category_url' => 'kesehatan',
                    'active' => 1,
                    'uuid' => $this->uuid()
                ]
            ]);
    }
}
