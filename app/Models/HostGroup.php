<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HostGroup extends Model
{
    use HasFactory;
    protected $table = "hostgroup";
    protected $hidden = ['pivot'];
    protected $fillable = [
        'file_id',
        'hostgroup_name',
        'action_url',
        'alias',
        // 'hostgroup_members',
        // 'members',
        'notes',
        'notes_url',
        'register',
    ];
    // protected $with = ["hostgroup_members"];
    public $timestamps = false;
    public function hosts()
    {
        return $this->belongsToMany(Host::class, 'host_hostgroup', 'hostgroup', 'host');
    }
    public function host_hostgroup()
    {
        return $this->hasMany(HostHostGroup::class, 'hostgroup');
    }

    public function hostgroup_members()
    {
        return $this->hasMany(HostGroupMember::class, 'hostgroup');
    }
    public function getHostGroupMembersAttribute()
    {
        $hostGroupIds = $this->hostgroup_members()->pluck('members');
        $hostNames = HostGroup::whereIn('id', $hostGroupIds)->pluck('hostgroup_name'); // Get an array of parent host names

        return $hostNames->toArray();
    }
    public function getMembersAttribute()
    {
        $hostIds = $this->host_hostgroup->pluck('host');
        $hostNames = Host::whereIn('id', $hostIds)->pluck('host_name'); // Get an array of parent host names

        return $hostNames->toArray();
    }

    public function file()
    {
        return $this->hasOne(File::class, 'id', 'file_id');
    }

    public function getFileNameAttribute()
    {
        return optional($this->file)->file_name;
    }

    public function toArray()
    {
        $array = parent::toArray();
        $array['file_id'] = $this->file_name;
        $array['member'] = $this->members;
        $array['hostgroup_members'] = $this->host_group_members;



        return $array;
    }
}
