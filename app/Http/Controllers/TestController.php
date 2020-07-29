<?php

/**
 * Created by PhpStorm.
 * User: fssha
 * Date: 5/1/2019
 * Time: 6:39 PM
 */

namespace App\Http\Controllers;

//ob_start();
//session_start();


use App\Delivery;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\QueryException;
use App\LocatedItem;

class TestController extends Controller
{

    public function checkSess()
    {
        return $_SESSION;
    }

    function create_delivery(Request $request)
    {
        if (!empty($request->ref_no)) {
            $_SESSION["ref_{$request->ref_no}"] = [];
            return [
                'status' => 200,
                'success' => true,
            ];
        } else
            return [
                'status' => 400,
                'success' => false,
            ];
    }

    function create_delivery_item(Request $request)
    {
        if (!empty($request->ref_no) && isset($_SESSION["ref_{$request->ref_no}"]) && !empty($request->roll_no)) {
            if (!in_array($request->roll_no, $_SESSION["ref_{$request->ref_no}"])) {
                $_SESSION["ref_{$request->ref_no}"]["{$request->roll_no}"] = $request->roll_info;

                return [
                    'status' => 200,
                    'success' => true,
                ];
            } else {

                return [
                    'status' => 400,
                    'success' => false,
                    'msg' => 'just have scanned now!!!'
                ];
            }
        } else
            return [
                'status' => 400,
                'success' => false,
            ];
    }

    function confirm_delivery(Request $request)
    {
        if (!empty($request->ref_no)) { }
    }

    function insertArray()
    {
        $array = [
            0 => [
                "reference" => 'ahsan',
            ],
            1 => [
                "reference" => '123',
            ],
            2 => [
                "reference" => '1234',
            ],
            4 => [
                "reference" => '1234',
            ],
        ];
        $data = [];
        $data = Delivery::insert($array);
        return response()->json($data);
    }


    
}
