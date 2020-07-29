<?php
/**
 * Created by PhpStorm.
 * User: fssha
 * Date: 5/2/2019
 * Time: 10:47 AM
 */

namespace App\libraries;


class CustomFunction
{
    public  static function roll_exists_in_delivery($_roll_no)
    {
        if(isset($_SESSION['delivery']) && is_array($_SESSION['delivery']))
            foreach($_SESSION['delivery'] as $ref_no=>$rolls)
                if(is_array($rolls))
                    foreach($rolls as $roll_no=>$roll_info)
                        if($roll_no==$_roll_no)
                            return $ref_no;
        return false;
    }
}