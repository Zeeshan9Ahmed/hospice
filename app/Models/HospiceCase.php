<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HospiceCase extends Model
{
    use HasFactory;
    protected $fillable = ['is_platform_fee_paid'];

    public function nurse_details(){
        return $this->belongsTo(User::class,'nurse_id','id','users');
    }

    public function feedback(){
        return $this->hasOne(Feedback::class,'post_id','id');
    }

    public function business_name(){
        return $this->hasOne(User::class,'id','user_id');
    }

    public function total_amount(){
        return $this->hasOne(RouteSheet::class,'case_id','id');
    }

    public function shifts(){
        return $this->hasMany(CaseShift::class,'case_id','id');
    }

}
