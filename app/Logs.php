<?php

namespace App;

use Illuminate\Database\Eloquent\Model;



class Logs extends Model
{

    // protected $guarded = [];

    function __construct($log=false){
        if(is_array($log))
        {
            $this->type         = isset($log['type']) ? $log['type'] : 'test';
            $this->title        = isset($log['title']) ? $log['title'] : null;
            $this->description  = isset($log['description']) ? $log['description'] : null;
            return $this->save();
        }else{
            return ['No parameter should be passed in Array.'];
        }
    }
}