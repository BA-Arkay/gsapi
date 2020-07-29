<?php
/**
 * Created by PhpStorm.
 * User: fssha
 * Date: 5/22/2019
 * Time: 6:25 PM
 */

namespace App\libraries;

use \Exception;

class Utility
{
    public static function api_output($header, $data=null){
        $output = [
            'status' => $header['status'],
            'success' => $header['success'],
            'message' => $header['message'],
            'description' => !$header['description'] ? null : $header['description'],
            'data' => !$data ? [] : $data
        ];

        return response()->json($output);
    }

    public static function _throw_exception($msg_title, $msg_desc = null)
    {
        throw new Exception("{$msg_title}|{$msg_desc}");
    }

}