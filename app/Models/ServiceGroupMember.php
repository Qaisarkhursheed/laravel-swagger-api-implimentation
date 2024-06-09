<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServiceGroupMember extends Model
{
    use HasFactory;
    protected $table = "servicegroup_members";
    protected $fillable = ["servicegroup","members"];
    public $timestamps = false;

}
