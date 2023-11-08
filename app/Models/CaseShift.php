<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CaseShift extends Model
{
    use HasFactory;

    public function nurse_details(){
        return $this->belongsTo(User::class,'nurse_id','id','case_shifts');
    }
}
