<?php

namespace App\Http\Controllers;

use App\Box;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Store;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\QueryException as Exception;
use Illuminate\Support\Facades\Validator;

class StoreController extends Controller
{
    public function index()
    {
        try {
            $stores = Store::where('id', '>', 1)->get();
            return response()->json($stores);
        } catch (Exception $e) {
            return response()->json($e->getMessage(), 403);
        }
    }

    public function indexDataTable()
    {
        try {
            $stores = Store::all();
            return response()->json($stores);
        } catch (Exception $e) {
            return response()->json($e->getMessage(), 403);
        }
    }

    public function store(Request $request)
    {
        try {

            $rules = array(
                "title" => 'required',
                "location" => 'required'
            );
            $json = [
                'status' => 200,
                'success' => false,
                'message' => '',
                'description' => '',
                'message_icon' => 'error',
                'message_type' => 'danger',
            ];
            $validator = Validator::make($request->all(), $rules);

            if ($validator->fails()) {
                $json['message'] = 'Store Title & Location is Required!';
                return response()->json($json);
            }
            $unique_store_title = Store::where('title',$request->title)->first();
            if ($unique_store_title) {
                $json['message'] = 'Store Title is already exists!';
                return response()->json($json);
            }
            $data['title'] = ucfirst(strtoupper($request->input('title')));
            $data['location'] = ucfirst(strtoupper($request->input('location')));
            $store = Store::create($data);

            $store->status = 201;
            $store->success = true;
            $store->message = 'Store Created!';
            $store->message_icon = 'success';
            $store->message_type = 'success';

            return response()->json($store);
        } catch (Exception $e) {

            return response()->json($e->getMessage(), 403);
        }

    }

    public function show($id)
    {
        try {
            $stores = Store::find($id);
            return response()->json($stores); //403
        } catch (Exception $e) {
            return response()->json($e->getMessage(), 403);
        }
    }

    public function destroy($id)
    {
        try {

            $output = [
                'status' => 200,
                'success' => false,
                'message' => '',
                'message_icon' => 'success',
                'store' => '',
                'description' => ''
            ];
            $store = Store::find($id);
            $output['store'] = $store->title;
            $boxes = Box::where('store_id', $id)->get();
            /*checking boxes of targeted store contain items of not*/
            foreach ($boxes as $box) {
                if ($box->number_of_items) {
                    $output['message_icon'] = 'error';
                    $output['message'] = "{$box->identifier} Contain {$box->number_of_items} Rolls";
                    return response()->json($output);
                }
            }
            $store->delete();
            $output['message'] = 'Store Removed!';
            $output['success'] = true;
            return response()->json($output);

        } catch (Exception $e) {
            return response()->json($e->getMessage(), 403);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $rules = array(
                "title" => 'unique:stores,title',
                "location" => 'required'
            );

            $validator = Validator::make($request->all(), $rules);
            $stores = Store::find($id);

            if ($validator->fails()) {
                $json = [
                    'success' => false,
                    'errors' => $validator->messages()
                ];
                $stores->location = ucfirst(strtoupper($request->input('location')));
                //return response()->json($json, 400);
            } else {
                $stores->title = ucfirst(strtoupper($request->input('title')));
                $stores->location = ucfirst(strtoupper($request->input('location')));
            }
            $stores->save();

            return response()->json($stores);
        } catch (Exception $e) {

            return response()->json($e->getMessage(), 403);
        }
    }
}

