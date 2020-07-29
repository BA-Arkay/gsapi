<?php
/**
 * Created by PhpStorm.
 * User: fssha
 * Date: 2/10/2019
 * Time: 8:34 PM
 */

namespace App\Http\Controllers;


use App\Settings;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\QueryException as Exception;
use Illuminate\Support\Facades\Input;
use Illuminate\Support\Facades\Validator;

class SettingsController extends Controller
{
    public function barcodeConfig()
    {
        try {
            $barcode = Settings::where('key','barcode')->first();
            return response()->json($barcode);
        } catch (Exception $e) {
            return response()->json($e->getMessage(), 403);
        }
    }

    public function unitConfig()
    {
        try {
            $unit = Settings::where('key','unit')->first();
            return response()->json($unit);
        } catch (Exception $e) {
            return response()->json($e->getMessage(), 403);
        }
    }

    public function apiItemWeightConfig()
    {
        try {
            $unit = Settings::where('key','api-item-weight')->first();
            return response()->json($unit);
        } catch (Exception $e) {
            return response()->json($e->getMessage(), 403);
        }
    }

    public function itemAgeConfig()
    {
        try {
            $unit = Settings::where('key','item-age')->first();
            return response()->json($unit);
        } catch (Exception $e) {
            return response()->json($e->getMessage(), 403);
        }
    }

    public function itemAgeUnitConfig()
    {
        try {
            $unit = Settings::where('key','item-age-unit')->first();
            return response()->json($unit);
        } catch (Exception $e) {
            return response()->json($e->getMessage(), 403);
        }
    }

    public function changeSettings(Request $request)
    {
        try {
            $setting = '';
            $key = $request->key;
            $value = $request->value;
            for ($i = 0 ; $i < count($key); $i++){
                $setting = Settings::where('key',$key[$i])->first();
                $setting->value = $value[$i];
                $setting->save();
            }

            return response()->json($setting);
        } catch (Exception $e) {

            return response()->json($e->getMessage(), 403);
        }
    }

        /*extra function*/
    public function show($id)
    {
        try {
            $settings = Settings::find($id);
            return response()->json($settings); //403
        } catch (Exception $e) {
            return response()->json($e->getMessage(), 403);
        }
    }

    public function store(Request $request)
    {
        try {

            $rules = array(
                "key" => 'required',
                "value" => 'required' //unique:settings,value
            );

            $validator = Validator::make($request->all(), $rules);

            if ($validator->fails()) {
                $json = [
                    'success' => false,
                    'errors' => $validator->messages()
                ];
                return response()->json($json, 400);
            }

            $setting = Settings::create($request->all());

            return response()->json($setting);
        } catch (Exception $e) {

            return response()->json($e->getMessage(), 403);
        }

    }
}