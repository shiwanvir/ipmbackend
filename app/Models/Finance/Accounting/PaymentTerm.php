<?php

namespace App\Models\Finance\Accounting;

use Illuminate\Database\Eloquent\Model;

use App\BaseValidator;

class PaymentTerm extends BaseValidator
{
    protected $table = 'fin_payment_term';
    protected $primaryKey = 'payment_term_id';
    const CREATED_AT = 'created_date';
    const UPDATED_AT = 'updated_date';

    protected $fillable = ['payment_code','payment_description','payment_term_id'];

    protected $rules = array(
        'payment_code' => 'required',
        'payment_description'  => 'required'
    );

    public function __construct()
    {
        parent::__construct();
    }


}
