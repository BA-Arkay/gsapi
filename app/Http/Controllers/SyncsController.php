<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Syncs;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\QueryException as Exception;
use Illuminate\Support\Facades\Validator;

class SyncsController extends Controller
{
    public function index()
    {
        try {
            $syncStores = Syncs::all();
            return response()->json($syncStores);
        } catch (Exception $e) {
            return response()->json($e->getMessage(), 403);
        }
    }

    public function show($id)
    {
        try {
            $syncStore = Syncs::find($id);
            return response()->json($syncStore); //403
        } catch (Exception $e) {
            return response()->json($e->getMessage(), 403);
        }
    }

    public function store(Request $request)
    {
        try {
            $rules = array(
                "item" => 'required',
                "weight" => 'required'
            );
            $validator = Validator::make($request->all(), $rules);
            if ($validator->fails()) {
                $json = [
                    'success' => false,
                    'errors' => $validator->messages()
                ];
                return response()->json($json, 400);
            }
            $data = $request->all();
            // return $data;
            $syncStore = Syncs::create($data);
            return response()->json($syncStore);
        } catch (Exception $e) {
            return response()->json($e->getMessage(), 403);
        }

    }

    public function destroy($id)
    {
        try {

            $syncStore = Syncs::find($id);
            $syncStore->delete();
            return response()->json($syncStore);

        } catch (Exception $e) {
            return response()->json($e->getMessage(), 403);
        }
    }

    public function update(Request $request, $id)
    {
        try {
            $rules = array(
                "item_identifier" => 'required',
                "weight" => 'required'
            );
            $validator = Validator::make($request->all(), $rules);
            if ($validator->fails()) {
                $json = [
                    'success' => false,
                    'errors' => $validator->messages()
                ];
                return response()->json($json, 400);
            }
            $syncStore = Store::find($id);
            $syncStore->title = $request->input('title');
            $syncStore->location = $request->input('location');

            $syncStore->save();

            return response()->json($syncStore);
        } catch (Exception $e) {

            return response()->json($e->getMessage(), 403);
        }
    }
}

