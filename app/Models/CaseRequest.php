<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CaseRequest extends Model
{
    use HasFactory;
    public function hospice_details(){
        return $this->belongsTo(User::class,'hospice_id','id','users');
    }
    public function case_details(){
        return $this->belongsTo(HospiceCase::class,'case_id','id','hospice_cases');
    }
}
