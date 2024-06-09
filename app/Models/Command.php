<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Command extends Model
{
    use HasFactory;
    protected $table = "command";
    protected $attributes = [
        'file_id' => 2,
    ];
    //in database there is table called file_tbl /opt/naemon/etc/naemon/conf.d/commands.cfg
    protected $fillable = [
        'command_name',
        'command_line',
        'register',
        'file_id',
    ];
    public $timestamps = false;
    public function file()
    {
        return $this->hasOne(File::class, 'id', 'file_id');
    }

    public function getFileNameAttribute()
    {
        return optional($this->file)->file_name;
    }
    public function contact()
    {
        return $this->belongsTo(Contact::class, 'service_notification_cmds', 'id');
    }
    public function toArray()
    {
        $array = parent::toArray();
        $array['file_id'] = $this->file_name;
        return $array;
    }
}
