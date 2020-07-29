<?php
/**
 * Created by PhpStorm.
 * User: fssha
 * Date: 2/22/2019
 * Time: 12:33 PM
 */

namespace App;


use Illuminate\Database\Eloquent\Model;

class Delivery extends Model
{
//    protected $fillable = ['reference','total_qty'];
    protected $guarded = [];

    function delivered_items()
    {
    	return $this->hasMany(DeliveredItem::class);
    }
}