<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NurseAvailability extends Model
{
    use HasFactory;

    public function nurse_details(){
        return $this->belongsTo(User::class,'user_id','id','users');
    }

    public function rating(){
        return $this->hasOne(Rating::class,'id','user_id');
    }
}
