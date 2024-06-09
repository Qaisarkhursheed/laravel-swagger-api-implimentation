<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HostContact extends Model
{
    use HasFactory;
    protected $table = "host_contact";
    protected $with = ["contact"];
    protected $fillable = ['host','contact'];

    public $timestamps = false;
    public function contact()
    {
        return $this->belongsTo(Contact::class, 'contact');
    }
}
