<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomVar extends Model
{
    use HasFactory;
    protected $table = "custom_vars";

    protected $fillable = ['obj_type','obj_id','variable','value'];

    public $timestamps = false;
}
