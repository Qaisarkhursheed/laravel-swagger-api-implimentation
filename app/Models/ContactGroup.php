<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ContactGroup extends Model
{
    use HasFactory;
    protected $table = "contactgroup";

    protected $fillable = [];
    public $timestamps = false;
    public function contacts()
    {
        return $this->belongsToMany(Contact::class, 'contact_contactgroup', 'contactgroup', 'contact');
    }

}
