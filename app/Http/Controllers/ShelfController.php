<?php

namespace App\Http\Controllers;

use App\Box;
use App\Http\Controllers\Controller;
use App\Rack;
use App\Store;
use Illuminate\Http\Request;
use App\Shelf;
use Illuminate\Database\QueryException as Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;


class ShelfController extends Controller
{
    public function index()
    {
        try {
            $shelf = Rack::with('store', 'shelves')
                ->where('id', '>', 1)
                ->get();
            return response()->json($shelf);
        } catch (Exception $e) {
            return response()->json($e->getMessage(), 403);
        }

    }

    public function indexDataTable()
    {
        try {
            $shelves = DB::table('shelves')
                ->join('stores', 'stores.id', '=', 'shelves.store_id')
                ->join('racks', 'racks.id', '=', 'shelves.rack_id')
                ->select('shelves.*', 'racks.title as rack', 'stores.title as store')
                ->where('racks.id', '>', 1)
                ->get();
            return response()->json($shelves);
        } catch (Exception $e) {
            return response()->json($e->getMessage(), 403);
        }
    }

    public function show($id)
    {

        $shelf = Shelf::find($id);

        return response()->json($shelf);
    }

    public function store(Request $request)
    {
        try {
            $rules = array(
                'store_id' => 'required',
                'rack_id' => 'required',
                'title' => 'required',
            );
            $json = [
                'status' => 200,
                'success' => false,
                'message' => '',
                'description' => '',
                'message_icon' => 'error',
            ];

            $validator = Validator::make($request->all(), $rules);
            if ($validator->fails()) {
                $json['message'] = 'Store, Rack & Title Required!';
				 $json['message_type'] = 'danger';
                $json['description'] = $validator->messages();

                return response()->json($json);
            }
            $uniqueCheck = Shelf::where('store_id', $request->store_id)
                ->where('rack_id', $request->rack_id)
                ->where('title', $request->title)->first();

            if ($uniqueCheck) {
                $json['message'] = 'Title has already been taken!';
                $json['message_type'] = 'danger';
                return response()->json($json);
            }

            $data['store_id'] = $request->input('store_id');
            $data['rack_id'] = $request->input('rack_id');
            $data['title'] = ucfirst(strtoupper($request->input('title')));
            // identifier
            $latestShelf = Shelf::orderBy('id', 'desc')
                ->first();
            $latestShelf ? $latestShelf = $latestShelf->id + 1 : $latestShelf = 1;

            $data['identifier'] = 's' . $request->store_id . '-r' . $request->rack_id . '-s' . $latestShelf;

            $shelf = Shelf::create($data);

            $shelf->status = 201;
            $shelf->store = Store::where('id', $shelf->store_id)->first()->title;
            $shelf->rack = Rack::where('id', $shelf->rack_id)->first()->title;
            $shelf->success = true;
            $shelf->message = 'Shelf Created!';
            $shelf->message_icon = 'success';
            $shelf->message_type = 'success';

            return response()->json($shelf);

        } catch (Exception $e) {
            return response()->json($e->getMessage(), 500);
        }
    }

    public function shelvesByRack($rackId)
    {
        $data = Shelf::where('rack_id', $rackId)->get();
        return response()->json($data);
    }

    public function destroy($id)
    {
        try {
            $output = [
                'status' => 200,
                'success' => false,
                'message' => '',
                'message_icon' => 'success',
                'shelf' => '',
                'description' => ''
            ];
            $shelf = Shelf::find($id);
            $output['shelf'] = $shelf->title;
            $boxes = Box::where('shelf_id',$id)->get();
            /*checking boxes of this shelf contain items of not*/
            foreach ($boxes as $box){
                if ($box->number_of_items){
                    $output['message_icon'] = 'error';
                    $output['message'] = "{$box->identifier} Contain {$box->number_of_items} Rolls";
                    return response()->json($output);
                }
            }

            $shelf->delete();
            $output['message'] = 'Shelf Removed!';
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
                //'store_id' => 'required',
                //'rack_id' => 'required',
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
            $shelf = Shelf::find($id);

            $uniqueCheck = Shelf::where('store_id', $shelf->store_id)
                ->where('rack_id', $shelf->rack_id)
                ->where('title', $request->title)->first();

            if ($uniqueCheck) {
                $json = [
                    'success' => false,
                    'errors' => 'This Title is already taken..'
                ];
                return response()->json($json, 400);
            }

            //$shelf->store_id = $request->input('store_id');
            //$shelf->rack_id = $request->input('rack_id');
            $shelf->title = ucfirst(strtoupper($request->input('title')));

            $shelf->save();

            return response()->json($shelf);
        } catch (Exception $e) {

            return response()->json($e->getMessage(), 500);
        }
    }
}
