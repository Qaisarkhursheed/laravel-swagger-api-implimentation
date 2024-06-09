<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServiceServiceGroup extends Model
{
    use HasFactory;
    protected $table = "service_servicegroup";
    // protected $with = ["servicegroup"];
    protected $fillable = ['service','servicegroup'];
    public $timestamps = false;

    public function servicegroup()
    {
        return $this->belongsTo(ServiceGroup::class,'servicegroup');
    }


}
