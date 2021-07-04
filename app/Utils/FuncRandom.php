<?php

namespace App\Utils;

use Illuminate\Support\Str;

trait FuncRandom
{
    public function uuid (){
        return Str::uuid()->toString();
    }

    public function uuid_short(){
        return rand(1000000000, 9999999999);
    }
}
