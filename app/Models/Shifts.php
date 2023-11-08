<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Shifts extends Model
{
    use HasFactory;
    protected $guarded = ['id'];

    protected $hidden = [
        'nurse_id',
    ];

    public function case_detail () {
        return $this->belongsTo(PatientCase::class,'case_id');
    }

    public function nurse () {
        return $this->belongsTo(User::class,'nurse_id');
    }
}
