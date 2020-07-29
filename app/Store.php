<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Store extends Model
{

//    protected $fillable = ['title', 'location'];
    protected $guarded = [];

    public function racks(){
        return $this->hasMany(Rack::class);
    }

}
