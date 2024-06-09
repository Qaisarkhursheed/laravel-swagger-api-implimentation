<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServiceGroup extends Model
{
    use HasFactory;
    protected $table = "servicegroup";
    // protected $with = ["service_servicegroup", "service_servicegroup", "servicegroup_members"];
    protected $hidden = ['pivot'];

    protected $fillable = [
        'file_id',
        'servicegroup_name',
        'action_url',
        'alias',
        'members',
        'notes',
        'notes_url',
        'register',
        'servicegroup_members',
    ];
    public $timestamps = false;
    public function services()
    {
        return $this->belongsToMany(Service::class, 'service_servicegroup', 'servicegroup', 'service');
    }
    public function service_servicegroup()
    {
        return $this->hasMany(ServiceServiceGroup::class, 'servicegroup');
    }

    public function servicegroup_members()
    {
        return $this->hasMany(ServiceGroupMember::class, 'servicegroup');
    }
    public function file()
    {
        return $this->hasOne(File::class, 'id', 'file_id');
    }

    public function getFileNameAttribute()
    {
        return optional($this->file)->file_name;
    }
    // public function service_servicegroup()
    // {
    //     // return $this->hasMany(ServiceServiceGroup::class, 'servicegroup');
    //     return $this->belongsToMany(ServiceGroup::class, 'service_servicegroup', 'service', 'servicegroup')->select('servicegroup_name');

    // }

    public function getMembersAttribute()
    {
        $serviceIds = $this->service_servicegroup->pluck('service');
        $serviceNames = Service::whereIn('id', $serviceIds)->pluck('service_description'); // Get an array of parent host names

        return $serviceNames->toArray();
    }
    public function getServiceGroupMembersAttribute()
    {
        $serviceGroupIds = $this->servicegroup_members()->pluck('members');
        $serviceGroupNames = ServiceGroup::whereIn('id', $serviceGroupIds)->pluck('servicegroup_name'); // Get an array of parent host names

        return $serviceGroupNames->toArray();
    }



    public function toArray()
    {
        $array = parent::toArray();
        $array['file_id'] = $this->file_name;
        $array['members'] = $this->members;
        $array['serviceegroup_members'] = $this->service_group_members;



        return $array;
    }
}
