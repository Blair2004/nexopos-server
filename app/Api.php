<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Api extends Model
{
    public static function code( $namespace )
    {
        return self::where( 'app_code', $namespace );
    }
}
