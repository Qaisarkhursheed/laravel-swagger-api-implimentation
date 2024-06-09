<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HostContactGroup extends Model
{
    use HasFactory;
    protected $table = "host_contactgroup";
    public $timestamps = false;
    protected $fillable = ['host','contactgroup'];
}
