<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShiftRequest extends Model
{
    use HasFactory;
    protected $guarded = ['id'];
    
    public function hospice_details(){
        return $this->belongsTo(User::class,'hospice_id','id','users');
    }
    public function case_details(){
        return $this->belongsTo(HospiceCase::class,'case_id','id','hospice_cases');
    }
    
    public function shift_details(){
        return $this->belongsTo(CaseShift::class,'shift_id','id','case_shifts');
    }
}
