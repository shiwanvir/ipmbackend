<?php

namespace App\Models\Finance;

use Illuminate\Database\Eloquent\Model;
use App\BaseValidator;

class ExchangeRate extends BaseValidator
{
    protected $table = 'org_exchange_rate';
    protected $primaryKey = 'id';
    //protected $keyType = 'varchar';
    const CREATED_AT = 'created_date';
    const UPDATED_AT = 'updated_date';

    protected $fillable = ['currency','rate','valid_from'];

    protected $rules = array(
        'currency' => 'required',
        'rate'  => 'required',
        'valid_from'  => 'required'
    );

    public function __construct()
    {
        parent::__construct();
    }

    public function setValidFromAttribute($value){
        $this->attributes['valid_from'] = date('Y-m-d', strtotime($value));
    }

    /*public function getValidFromAttribute($value){
        $this->attributes['valid_from'] = date('d F,Y', strtotime($value));
        return $this->attributes['valid_from'];
    }*/


    //default currency of the exchange rate
		public function currency()
		{
			 return $this->belongsTo('App\Models\Finance\Currency' , 'currency');
		}


}
