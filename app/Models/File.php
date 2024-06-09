<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class File extends Model
{
    use HasFactory;
    protected $table = "file_tbl";
    public function host()
    {
        return $this->belongsTo(Host::class, 'file_id');
    }
    // public function service()
    // {
    //     return $this->belongsTo(Service::class, 'file_id');
    // }
}
