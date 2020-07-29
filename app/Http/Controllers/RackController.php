<?php

namespace App\Http\Controllers;

use App\Box;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Rack;
use App\Store;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Input;
use Illuminate\Database\QueryException as Exception;
use Illuminate\Support\Facades\Validator;


class RackController extends Controller
{
    public function index()
    {
        try {
            $racks = Store::with('racks')->where('id', '>', 1)->get();
            //$racks = Rack::all();
            /*$racks = DB::table('racks')
                        ->join('stores', 'stores.id', '=', 'racks.store_id')
                        ->select('racks.*', 'stores.title')
                        ->get();*/
            //$racks = DB::select('SELECT racks.*, stores.title as store FROM `racks`, stores WHERE stores.id = racks.store_id');
            return response()->json($racks);
        } catch (Exception $e) {
            return response()->json($e->getMessage(), 403);
        }

    }

    public function indexDataTable()
    {
        try {
            $racks = DB::table('racks')
                ->join('stores', 'stores.id', '=', 'racks.store_id')
                ->select('racks.*', 'stores.title as store')
                ->where('racks.id', '>', 1)
                ->get();
            return response()->json($racks);
        } catch (Exception $e) {
            return response()->json($e->getMessage(), 403);
        }

    }

    public function show($id)
    {
        $rack = Rack::find($id);
        return response()->json($rack);
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        try {
            $rules = array(
                'store_id' => 'required',
                'title' => 'required',
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
                $json['message'] = 'Store & Title Required!';
                $json['description'] = $validator->messages();
                return response()->json($json);
            }
            $uniqueCheck = Rack::where('store_id', $request->store_id)
                ->where('title', $request->title)->first();
            if ($uniqueCheck) {
                $json['message'] = 'Title has been already taken!';
                return response()->json($json);
            }
            $data['store_id'] = ucfirst(strtolower($request->input('store_id')));
            $data['title'] = ucfirst(strtolower($request->input('title')));

            // identifier
            // $data['identifier'] = 'r'.$request->store_id.str_replace(' ', '', $request->title);
            $latestRack = Rack::orderBy('id', 'desc')
                ->first();

            ($latestRack) ? $latestRack = $latestRack->id + 1 : $latestRack = 1;

            $data['identifier'] = 's' . $request->store_id . '-r' . $latestRack;

            $rack = Rack::create($data);

            $rack->store = Store::where('id', $rack->store_id)->first()->title;
            $rack->status = 201;
            $rack->success = true;
            $rack->message = 'Rack Created!';
            $rack->description = 'Rack has been successfully created!';
            $rack->message_icon = 'success';
            $rack->message_type = 'success';
            return response()->json($rack);

        } catch (Exception $e) {
            return response()->json($e->getMessage(), 403);
        }

    }

    public function racksByStore($storeId)
    {
        try {
            $data = Rack::where('store_id', $storeId)->get();
            return response()->json($data);
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
                'rack' => '',
                'description' => ''
            ];
            $rack = Rack::find($id);
            $output['rack'] = $rack->title;
            $boxes = Box::where('rack_id',$id)->get();
            /*checking boxes of targeted Rack contain items of not*/
            foreach ($boxes as $box){
                if ($box->number_of_items){
                    $output['message_icon'] = 'error';
                    $output['message'] = "{$box->identifier} Contain {$box->number_of_items} Rolls";
                    return response()->json($output);
                }
            }

            $rack->delete();
            $output['message'] = 'Rack Removed!';
            $output['success'] = true;
            return response()->json($output);
        } catch (Exception $e) {
            return response()->json($e->getMessage(), 500);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $rules = array(
//                'store_id' => 'required',
                'title' => 'required',
            );

            $validator = Validator::make($request->all(), $rules);

            if ($validator->fails()) {
                $json = [
                    'success' => false,
                    'errors' => $validator->messages()
                ];
                return response()->json($json, 400);
            }
            $rack = Rack::find($id);

            $uniqueCheck = Rack::where('store_id', $rack->store_id)
                ->where('title', $request->title)->first();
            if ($uniqueCheck) {
                $json = [
                    'success' => false,
                    'errors' => 'Title is already taken..'
                ];
                return response()->json($json, 400);
            }
            //$rack->store_id = $request->input('store_id');
            $rack->title = ucfirst(strtolower($request->input('title')));

            $rack->save();
            return response()->json($rack);
        } catch (Exception $e) {
            return response()->json($e->getMessage(), 500);
        }
    }
}
