<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Utils\FuncRandom;

class Users extends Seeder
{
    use FuncRandom;
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('users')
            ->insert([
                [
                    'id' => $this->uuid_short(),
                    'username' => 'Kadal25',
                    'email' => 'antoniusa@gmail.com',
                    'password' => Hash::make('anton250997',[
                        'memory' => 1024,
                        'time' => 2,
                        'threads' => 2,
                    ]),
                    'name' => 'Antonius A',
                    'gender' => 1,
                    'nohp' => '08999239161',
                    'address' => 'Perum Palm Indah',
                    'experience' => 1,
                    'photo' => null,
                    'token' => $this->uuid(),
                    'role' => 'admin',
                    'active' => 1,
                    'uuid' => $this->uuid()
                ],
                [
                    'id' => $this->uuid_short(),
                    'username' => 'Kadal48',
                    'email' => 'ira.a@gmail.com',
                    'password' => Hash::make('Kadal250997',[
                        'memory' => 1024,
                        'time' => 2,
                        'threads' => 2,
                    ]),
                    'name' => 'Ira A',
                    'gender' => 0,
                    'nohp' => '08999239261',
                    'address' => 'Medan',
                    'experience' => 1,
                    'photo' => null,
                    'token' => $this->uuid(),
                    'role' => 'author',
                    'active' => 1,
                    'uuid' => $this->uuid()
                ],
                [
                    'id' => $this->uuid_short(),
                    'username' => 'Kadal46',
                    'email' => 'ulfa.mulya@gmail.com',
                    'password' => Hash::make('Ulfa250997',[
                        'memory' => 1024,
                        'time' => 2,
                        'threads' => 2,
                    ]),
                    'name' => 'Ulfa Mulya',
                    'gender' => 0,
                    'nohp' => '08999210261',
                    'address' => 'Jogja',
                    'experience' => null,
                    'photo' => null,
                    'token' => $this->uuid(),
                    'role' => 'reader',
                    'active' => 1,
                    'uuid' => $this->uuid()
                ]
            ]);
    }
}
