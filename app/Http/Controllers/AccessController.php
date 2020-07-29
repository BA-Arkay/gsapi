<?php
/**
 * Created by PhpStorm.
 * User: fssha
 * Date: 6/9/2019
 * Time: 9:55 PM
 */

namespace App\Http\Controllers;


use App\libraries\Utility;

class AccessController extends Controller
{
    public function getAccessToken(){
        $api_header = [
            'status' => 200,
            'success' => true,
            'message' => 'Access Token Found!',
            'description' => ''
        ];
        $data = [
            'access_token' => env('ACCESS_TOKEN') ? env('ACCESS_TOKEN') : '',
        ];
        return Utility::api_output($api_header, $data);
    }

}