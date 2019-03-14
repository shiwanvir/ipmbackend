<?php

namespace App\Models\Merchandising;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class bom_details extends Model
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'bom_details';

    /**
    * The database primary key value.
    *
    * @var string
    */
    protected $primaryKey = 'bom_id';

    /**
     * Attributes that should be mass-assignable.
     *
     * @var array
     */

    public $timestamps = false;

    protected $fillable = ['bom_id','combine_id','master_id','item_color','item_size','unit_price','conpc','total_qty','total_value','supplier_id','artical_no','status','bal_qty','order_id','uom_id'];

    public function GetBOMDetails($bomId){

        return DB::table('bom_details')
                  ->join('item_master','item_master.master_id','bom_details.master_id')
                  ->join('item_subcategory', 'item_subcategory.subcategory_id','item_master.subcategory_id')
                  ->join('item_category','item_category.category_id','item_subcategory.category_id')
                  ->join('org_color','org_color.color_id','bom_details.item_color')
                  ->join('org_size','org_size.size_id','bom_details.item_size')
                  ->join('org_uom','org_uom.uom_id','bom_details.uom_id')
                  ->join('bom_header','bom_header.bom_id','bom_details.bom_id')
                  ->leftJoin('org_supplier','org_supplier.supplier_id','=','bom_details.supplier_id')
                  ->join('costing_bulk_details',function($join){
                    $join->on('costing_bulk_details.bulkheader_id','=','bom_header.costing_id')
                         ->on('costing_bulk_details.item_id','=','bom_details.master_id');
                    })
                  ->leftJoin('merc_color_options','merc_color_options.col_opt_id','=','costing_bulk_details.color_type_id')
                  ->leftJoin('merc_cut_direction','merc_cut_direction.cut_dir_id','=','costing_bulk_details.cut_dir_id')
                  ->select('item_master.master_id','item_master.master_description','bom_details.artical_no','org_color.color_name','org_size.size_name','org_uom.uom_description','org_uom.uom_id','bom_details.conpc','bom_details.item_wastage','bom_details.unit_price','bom_details.total_qty','bom_details.total_value','org_color.color_id','costing_bulk_details.moq','costing_bulk_details.mcq','org_size.size_id','org_supplier.supplier_name','org_supplier.supplier_id','merc_color_options.color_option','merc_cut_direction.cut_dir_description','item_category.category_code')
                  ->where('bom_details.bom_id',$bomId)->get();


                  /*
                         ->on('costing_bulk_details.color_id','=','bom_details.item_color')
                         ->on('costing_bulk_details.size_id','=','bom_details.item_size');*/
    }
}
