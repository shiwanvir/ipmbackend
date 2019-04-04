<?php

namespace App\Http\Controllers\Merchandising;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Database\Eloquent\ModelNotFoundException;

use App\Http\Controllers\Controller;
use App\Models\Merchandising\Position;
use Exception;
class PositionController extends Controller
{

      public function __construct()
      {
        //add functions names to 'except' paramert to skip authentication
        $this->middleware('jwt.verify', ['except' => ['index']]);
      }

      //get Origin Type list
      public function index(Request $request)
      {
        $type = $request->type;
        if($type == 'datatable')   {
          $data = $request->all();
          return response($this->datatable_search($data));
        }
        else if($type == 'auto')    {
          $search = $request->search;
          return response($this->autocomplete_search($search));
        }
        else {
          $active = $request->active;
          $fields = $request->fields;
          return response([
            'data' => $this->list($active , $fields)
          ]);
        }
      }


      //create a Origin Type
      public function store(Request $request)
      {
        $position = new Position;
        if($position->validate($request->all()))
        {
          $position->fill($request->all());
          $position->status = 1;
          $position->save();

          return response([ 'data' => [
            'message' => 'position was saved successfully',
            'position' => $position
            ]
          ], Response::HTTP_CREATED );
        }
        else
        {
            $errors = $position->errors();// failure, get errors
            return response(['errors' => ['validationErrors' => $errors]], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
      }


      //get a Origin Type
      public function show($id)
      {
        $position = Position::find($id);
        if($position == null)
          throw new ModelNotFoundException("Requested Position not found", 1);
        else
          return response([ 'data' => $position ]);
      }


      //update a Origin Type
      public function update(Request $request, $id)
      {
        $position = Position::find($id);
        if($position->validate($request->all()))
        {
          $position->fill($request->all());
          $position->save();

          return response([ 'data' => [
            'message' => 'Position was updated successfully',
            'position' => $position
          ]]);
        }
        else
        {
          $errors = $position->errors();// failure, get errors
          return response(['errors' => ['validationErrors' => $errors]], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
      }


      //deactivate a Origin Type
      public function destroy($id)
      {
        $position = Position::where('position_id', $id)->update(['status' => 0]);
        return response([
          'data' => [
            'message' => 'Position was deactivated successfully.',
            'position' => $position
          ]
        ] , Response::HTTP_NO_CONTENT);
      }


      //validate anything based on requirements
      public function validate_data(Request $request){
        $for = $request->for;
        if($for == 'duplicate')
        {
          return response($this->validate_duplicate_code($request->position_id , $request->position));
        }
      }


      //check OriginType code already exists
      private function validate_duplicate_code($id , $code)
      {
        $position = Position::where('position','=',$code)->first();
        if($position == null){
          return ['status' => 'success'];
        }
        else if($position->position_id == $id){
          return ['status' => 'success'];
        }
        else {
          return ['status' => 'error','message' => 'Position alrady exists'];
        }
      }


      //get filtered fields only
      private function list($active = 0 , $fields = null)
      {
        $query = null;
        if($fields == null || $fields == '') {
          $query = Position::select('*');
        }
        else{
          $fields = explode(',', $fields);
          $query =Position::select($fields);
          if($active != null && $active != ''){
            $query->where([['status', '=', $active]]);
          }
        }
        return $query->get();
      }

      //search Origin Type for autocomplete
      private function autocomplete_search($search)
    	{
    		$position_lists = Position::select('position_id','Position')
    		->where([['position', 'like', '%' . $search . '%'],]) ->get();
    		return $position_lists;
    	}


      //get searched OriginTypes for datatable plugin format
      private function datatable_search($data)
      {
        $start = $data['start'];
        $length = $data['length'];
        $draw = $data['draw'];
        $search = $data['search']['value'];
        $order = $data['order'][0];
        $order_column = $data['columns'][$order['column']]['data'];
        $order_type = $order['dir'];

        $position_lists =Position::select('*')
        ->where('position'  , 'like', $search.'%' )
        ->orderBy($order_column, $order_type)
        ->offset($start)->limit($length)->get();

      $position_count = Position::where('position'  , 'like', $search.'%' )->count();

        return [
            "draw" => $draw,
            "recordsTotal" => $position_count,
            "recordsFiltered" =>$position_count,
            "data" => $position_lists
        ];
      }







}
