<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ContactGroupMembers extends Model
{
    use HasFactory;
    protected $table = "contactgroup_members";

    protected $fillable = ['contactgroup','members'];
    public $timestamps = false;
}
