<?php

namespace App;

use Illuminate\Database\Eloquent\Model;



class Shelf extends Model
{
//    protected $fillable = ['store_id', 'rack_id', 'title', 'identifier'];
    protected $guarded = [];

    public function store(){
        return $this->belongsTo(\App\Store::class);
    }
    public function rack(){
        return $this->belongsTo(\App\Rack::class);
    }
    public function boxes(){
        return $this->hasMany(\App\Box::class);
    }
}