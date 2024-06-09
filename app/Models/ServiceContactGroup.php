<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServiceContactGroup extends Model
{
    use HasFactory;
    protected $fillable = ['service','contactgroup'];
    public $timestamps = false;
    protected $table = "service_contactgroup";

    protected $with = ["contactgroup"];

    public function contactgroup()
    {
        return $this->belongsTo(ContactGroup::class, 'contactgroup');
    }

}
