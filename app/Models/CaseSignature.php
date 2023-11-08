<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CaseSignature extends Model
{
    use HasFactory;
    protected $hidden = [
        'nurse_id',
        'case_id',
    ];

    protected $guarded = ['id'];
}
