<?php

namespace App\Http\Controllers\Org;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Database\Eloquent\ModelNotFoundException;

use App\Http\Controllers\Controller;
use App\Models\Org\Season;
use Exception;

class SeasonController extends Controller
{
    public function __construct()
    {
      //add functions names to 'except' paramert to skip authentication
      $this->middleware('jwt.verify', ['except' => ['index','GetSeasonsList']]);
    }

    //get Season list
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


    //create a Season
    public function store(Request $request)
    {
      $season = new Season();
      if($season->validate($request->all()))
      {
        $season->fill($request->all());
        $season->status = 1;
        $season->save();

        return response([ 'data' => [
          'message' => 'Season was saved successfully',
          'season' => $season
          ]
        ], Response::HTTP_CREATED );
      }
      else
      {
          $errors = $season->errors();// failure, get errors
          return response(['errors' => ['validationErrors' => $errors]], Response::HTTP_UNPROCESSABLE_ENTITY);
      }
    }


    //get a Season
    public function show($id)
    {
      $season = Season::find($id);
      if($season == null)
        throw new ModelNotFoundException("Requested season not found", 1);
      else
        return response([ 'data' => $season ]);
    }


    //update a Season
    public function update(Request $request, $id)
    {
      $season = Season::find($id);
      if($season->validate($request->all()))
      {
        $season->fill($request->except('season_code'));
        $season->save();

        return response([ 'data' => [
          'message' => 'Season was updated successfully',
          'season' => $season
        ]]);
      }
      else
      {
        $errors = $season->errors();// failure, get errors
        return response(['errors' => ['validationErrors' => $errors]], Response::HTTP_UNPROCESSABLE_ENTITY);
      }
    }


    //deactivate a Season
    public function destroy($id)
    {
      $season = Season::where('season_id', $id)->update(['status' => 0]);
      return response([
        'data' => [
          'message' => 'Season was deactivated successfully.',
          'Season' => $season
        ]
      ] , Response::HTTP_NO_CONTENT);
    }


    //validate anything based on requirements
    public function validate_data(Request $request){
      $for = $request->for;
      if($for == 'duplicate')
      {
        return response($this->validate_duplicate_code($request->season_id , $request->season_code));
      }
    }


    //check Season code already exists
    private function validate_duplicate_code($id , $code)
    {
      $season = Season::where('season_code','=',$code)->first();
      if($season == null){
        return ['status' => 'success'];
      }
      else if($season->season_id == $id){
        return ['status' => 'success'];
      }
      else {
        return ['status' => 'error','message' => 'Season code already exists'];
      }
    }


    //get filtered fields only
    private function list($active = 0 , $fields = null)
    {
      $query = null;
      if($fields == null || $fields == '') {
        $query = Season::select('*');
      }
      else{
        $fields = explode(',', $fields);
        $query = Season::select($fields);
        if($active != null && $active != ''){
          $query->where([['status', '=', $active]]);
        }
      }
      return $query->get();
    }

    //search Season for autocomplete
    private function autocomplete_search($search)
  	{
  		$season_lists = Season::select('season_id','season_name')
  		->where([['season_name', 'like', '%' . $search . '%'],]) ->get();
  		return $season_lists;
  	}


    //get searched Seasons for datatable plugin format
    private function datatable_search($data)
    {
      $start = $data['start'];
      $length = $data['length'];
      $draw = $data['draw'];
      $search = $data['search']['value'];
      $order = $data['order'][0];
      $order_column = $data['columns'][$order['column']]['data'];
      $order_type = $order['dir'];

      $season_list = Season::select('*')
      ->where('season_code'  , 'like', $search.'%' )
      ->orWhere('season_name'  , 'like', $search.'%' )
      ->orderBy($order_column, $order_type)
      ->offset($start)->limit($length)->get();

      $season_count = Season::where('season_code'  , 'like', $search.'%' )
      ->orWhere('Season_name'  , 'like', $search.'%' )
      ->count();

      return [
          "draw" => $draw,
          "recordsTotal" => $season_count,
          "recordsFiltered" => $season_count,
          "data" => $season_list
      ];
    }
    
    public function GetSeasonsList(){
        
        $seasons_list = Season::all();
        echo json_encode($seasons_list);
        
    }

}
