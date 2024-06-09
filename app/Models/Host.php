<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Host extends Model
{
    use HasFactory;
    protected $table = "host";
    // protected $with = ["host_contact","host_parents"];
    protected $hidden = ['file'];

    protected $fillable = [
        'host_name',
        'address',
        'template',
        "name",
        'register',
        'file_id',
        'check_command',
        'max_check_attempts',
        'check_interval',
        'retry_interval',
        'active_checks_enabled',
        'passive_checks_enabled',
        'check_period',
        'event_handler_enabled',
        'flap_detection_enabled',
        'process_perf_data',
        'retain_status_information',
        'retain_nonstatus_information',
        'notification_interval',
        'notification_period',
        'notification_options',
        'notifications_enabled',
        'alias',
        'display_name',
        // 'hostgroups',
        // 'parents',
        // 'children',
        'check_command_args',
        // 'contacts',
        // 'contact_groups',
        'obsess',
        'check_freshness',
        'freshness_threshold',
        'event_handler',
        'event_handler_args',
        'low_flap_threshold',
        'high_flap_threshold',
        'flap_detection_options',
        'first_notification_delay',
        'stalking_options',
        'icon_image',
        'icon_image_alt',
        'statusmap_image',
        'notes',
        'action_url',
        'notes_url',
        '2d_coords',
        'obsess_over_host',
        'services',
    ];
    public $timestamps = false;
    protected $casts = [
        'stalking_options' => 'array',
        'flap_detection_options' => 'array',
        'notification_options' => 'array'
    ];
    /**
     * Get the value of the "satalking_options" attribute as an array.
     *
     * @param  string  $value
     * @return array
     */
    public function getStalkingOptionsAttribute($value)
    {
        if($value)
        {
            return explode(',', $value);
        }
        return [];

    }

    /**
     * Set the value of the "satalking_options" attribute as a comma-separated string.
     *
     * @param  mixed  $value
     * @return void
     */
    public function setStalkingOptionsAttribute($value)
    {

            $this->attributes['stalking_options'] = implode(',', $value);
    }

    /**
     * Get the value of the "flap_detection_options" attribute as an array.
     *
     * @param  string  $value
     * @return array
     */
    public function getFlapDetectionOptionsAttribute($value)
    {
        if($value)
            return explode(',', $value);
        else
            return [];
    }

    /**
     * Set the value of the "flap_detection_options" attribute as a comma-separated string.
     *
     * @param  mixed  $value
     * @return void
     */
    public function setFlapDetectionOptionsAttribute($value)
    {
        $this->attributes['flap_detection_options'] = implode(',', $value);
    }

    /**
     * Set the value of the "notification_options" attribute as a comma-separated string.
     *
     * @param  mixed  $value
     * @return void
     */
    public function setNotificationOptionsAttribute($value)
    {
        $this->attributes['notification_options'] = implode(',', $value);
    }
    /**
     * Get the value of the "notification_options" attribute as an array.
     *
     * @param  string  $value
     * @return array
     */
    public function getNotificationOptionsAttribute($value)
    {
        if($value)
            return explode(',', $value);
        else
            return [];
    }


    public function hostGroup()
    {
        return $this->belongsToMany(HostGroup::class, 'host_hostgroup', 'host', 'hostgroup')->select('hostgroup_name');
    }
    // public function getHostGroupAttribute($value)
    // {
    //     return $this->hostGroup->pluck('hostgroup_name')->toArray();
    // }
    public function host_contact()
    {
        // return $this->hasMany(HostContact::class, 'host', 'id');

        return $this->belongsToMany(Contact::class, 'host_contact', 'host', 'contact')->select('contact_name');

    }
    public function host_contact_groups()
    {
        return $this->belongsToMany(ContactGroup::class, 'host_contactgroup', 'host', 'contactgroup')->select('contactgroup_name');
    }

    public function getHostContactGroupAttribute()
    {
        $names = $this->host_contact_groups->toArray();

        // Extract only the contactgroup_name values
        $groupNames = array_map(function ($group) {
            return $group['contactgroup_name'];
        }, $names);

        return $groupNames;
    }
    public function host_hostgroup()
    {
        return $this->belongsToMany(HostGroup::class, 'host_hostgroup', 'host', 'hostgroup')->select('hostgroup_name');

    }

    public function host_parents()
    {
        return $this->hasMany(HostParent::class, 'host', 'id');
    }


    public function file()
    {
        return $this->hasOne(File::class, 'id', 'file_id');
    }

    // get host parents name from the Host model itself
    public function getHostParentNamesAttribute()
    {
        $hostParentIds = $this->host_parents->pluck('parents');
        $parentHostNames = Host::whereIn('id', $hostParentIds)->pluck('host_name'); // Get an array of parent host names

        return $parentHostNames->toArray();
    }
    public function getHostgroupNamesAttribute()
    {
        $names = $this->host_hostgroup->pluck('hostgroup_name');
        $parentHostNames = HostGroup::whereIn('hostgroup_name', $names)->pluck('hostgroup_name'); // Get an array of parent host names

        return $parentHostNames->toArray();
    }

    public function getFileNameAttribute()
    {
        return optional($this->file)->file_name;
    }
    public function getTemplateNameAttribute()
    {
        $template = Host::where('id',$this->template)->where('register',0)->first();
        return $template ? $template->name : null;
    }
    public function command()
    {
        return $this->hasOne(Command::class, 'id', 'check_command');
    }
    public function getCommandActualNameAttribute()
    {
        return optional($this->command)->command_name;
    }

    // get time periods from the model TimePeriod
    public function checkPeriod()
    {
        return $this->hasOne(TimePeriod::class, 'id', 'check_period');
    }
    public function notificationPeriod()
    {
        return $this->hasOne(TimePeriod::class, 'id', 'notification_period');
    }

    // create a attribute on runtime for the model and later in toArray function append it to the response as string

    // for the check_periods
    public function getCheckPeriodActualNameAttribute()
    {
        return optional($this->checkPeriod)->timeperiod_name;
    }

    // for notification_period
    public function getNotificationPeriodActualNameAttribute()
    {
        return optional($this->notificationPeriod)->timeperiod_name;
    }
    public function services()
    {
        return $this->hasMany(Service::class, 'host_name', 'id');
    }

    // getting the custom variables from the custom_vars table for host only
    public function customVariables()
    {
        return $this->hasMany(CustomVar::class, 'obj_id')->where('obj_type', 'host');
    }


    public function toArray()
    {
        $array = parent::toArray();
        $array['template'] = $this->template_name;
        $array['file_id'] = $this->file_name;
        $array['check_command'] = $this->command_actual_name;
        $array['notification_period'] = $this->notification_period_actual_name;
        $array['check_period'] = $this->check_period_actual_name;
        $array['host_parents'] = $this->host_parent_names;
        // $array['host_hostgroup'] = $this->host_group_names;
        $array['host_contactgroup'] = $this->host_contact_group;
        $customVariables = $this->customVariables->pluck('value', 'variable')->toArray();
        $array['services'] = $this->services;

        $array = array_merge($array, $customVariables);
        return $array;
    }


}
