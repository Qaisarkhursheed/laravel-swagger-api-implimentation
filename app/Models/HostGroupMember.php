<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HostGroupMember extends Model
{
    use HasFactory;
    protected $table = "hostgroup_members";
    protected $fillable = ["hostgroup","members"];
    public $timestamps = false;
}
