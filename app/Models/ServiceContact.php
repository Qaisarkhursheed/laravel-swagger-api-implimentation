<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServiceContact extends Model
{
    use HasFactory;
    protected $table = "service_contact";
    protected $with = ["contact"];
    protected $fillable = ['service','contact'];
    public $timestamps = false;

    public function contact()
    {
        return $this->belongsTo(Contact::class, 'contact');
    }
}
