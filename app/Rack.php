<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use App\Store;

class Rack extends Model
{
//    protected $fillable = ['store_id','title','identifier'];
    protected $guarded = [];
    public function store(){
        return $this->belongsTo(Store::class);
    }
     public function shelves(){
        return $this->hasMany(\App\Shelf::class);
    }

}
