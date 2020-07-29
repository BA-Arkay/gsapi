<?php
/**
 * Created by PhpStorm.
 * User: fssha
 * Date: 2/22/2019
 * Time: 12:33 PM
 */

namespace App;


use Illuminate\Database\Eloquent\Model;

class DeliveredItem extends Model
{
//    protected $fillable = ['delivery_id','item_identifier','quantity'];
    protected $guarded = [];

    public function delivery(){
    	return $this->belongsTo(Delivery::class);
    }
}