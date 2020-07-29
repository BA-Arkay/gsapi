<?php

namespace App;

use Illuminate\Database\Eloquent\Model;



class Box extends Model
{
//    protected $fillable = [
//        'store_id',
//        'barcode',
//        'rack_id',
//        'shelf_id',
//        'title',
//        'identifier',
//        'capacity',
//    ];

    protected $guarded = [];

    public function shelve(){
        return $this->belongsTo(\App\Shelf::class);
    }
}