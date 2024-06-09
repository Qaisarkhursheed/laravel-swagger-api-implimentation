<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HostHostGroup extends Model
{
    use HasFactory;
    protected $table = "host_hostgroup";
    protected $fillable = ['host','hostgroup'];
    public $timestamps = false;
}
