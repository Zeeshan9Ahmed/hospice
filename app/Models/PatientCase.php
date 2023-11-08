<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PatientCase extends Model
{
    use HasFactory;
    protected $guarded = ['id'];

    public function shifts()
    {
        return $this->hasMany(Shifts::class, 'case_id');
    }


    public function available_shifts()
    {
        return $this->hasMany(Shifts::class, 'case_id');
    }

    public function booked_shifts()
    {
        return $this->hasMany(Shifts::class, 'case_id')->where('nurse_id', auth()->id());
    }

    public function business_name()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function case_signature()
    {
        return $this->hasOne(CaseSignature::class, 'case_id'); //->where('nurse_id', auth()->id());
    }

    public function feedback()
    {
        return $this->hasOne(Feedback::class, 'post_id')->orderByDesc('id');
    }
}
