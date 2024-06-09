<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ContactContactGroup extends Model
{
    use HasFactory;
    protected $table = "contact_contactgroup";
    protected $with = ["contactgroup"];
    protected $fillable = ['contactgroup','contact'];
    public $timestamps = false;

    public function contactgroup()
    {
        return $this->belongsTo(ContactGroup::class, 'contactgroup');
    }
}
