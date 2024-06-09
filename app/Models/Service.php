<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    use HasFactory;
    protected $table = "service";
    // protected $with = ["service_contactgroup"];
    protected $fillable = [
        'host_name',
        'name',
        'service_description',
        'check_command',
        'check_command_args',
        'obsess',
        'template',
        'register',
        'file_id',
        'is_volatile',
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
        'hostgroup_name',
        'display_name',
        // 'servicegroups',
        'parallelize_check',
        'check_freshness',
        'freshness_threshold',
        'event_handler',
        'event_handler_args',
        'low_flap_threshold',
        'high_flap_threshold',
        'flap_detection_options',
        'first_notification_delay',
        // 'contacts',
        // 'contact_groups',
        'stalking_options',
        'notes',
        'notes_url',
        'action_url',
        'icon_image',
        'icon_image_alt',
        // 'obsess_over_service',
    ];
    public $timestamps = false;
    public static $rules = [
        'check_command' => 'string',
        'check_command_args' => 'string',
        'obsess' => 'boolean',
        'template' => 'string',
        'register' => 'nullable|boolean',
        'is_volatile' => 'boolean',
        'max_check_attempts' => 'integer',
        'check_interval' => 'integer',
        'retry_interval' => 'integer',
        'active_checks_enabled' => 'boolean',
        'passive_checks_enabled' => 'boolean',
        'check_period' => 'string',
        'event_handler_enabled' => 'boolean',
        'flap_detection_enabled' => 'boolean',
        'process_perf_data' => 'boolean',
        'retain_status_information' => 'boolean',
        'retain_nonstatus_information' => 'boolean',
        'notification_period' => 'string',
        'notification_options' => 'array',
        'notification_options.*' => 'in:c,w,u,r,f',
        'notifications_enabled' => 'boolean',
        // 'hostgroup_name' => 'string',
        'display_name' => 'string',
        'servicegroups' => 'array',
        'parallelize_check' => 'boolean',
        'check_freshness' => 'boolean',
        'freshness_threshold' => 'integer',
        'event_handler' => 'string',
        'event_handler_args' => 'string',
        'low_flap_threshold' => 'integer',
        'high_flap_threshold' => 'integer',
        'flap_detection_options' => 'array',
        'first_notification_delay' => 'integer',
        'contacts' => 'array',
        'contact_groups' => 'array',
        'stalking_options' => 'array|nullable',
        'stalking_options.*' => 'in:n',
        'notes' => 'string',
        'notes_url' => 'string',
        'action_url' => 'string',
        'icon_image' => 'string',
        'icon_image_alt' => 'string',
        'obsess_over_service' => 'boolean',
        'name' => "nullable | string"
    ];
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
    public function getStalkingOptionsAttribute($value=[])
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
    public function setStalkingOptionsAttribute($value=[])
    {
        if(isset($value))
            $this->attributes['stalking_options'] = implode(',', $value);
        else
          return [];
    }

    /**
     * Get the value of the "flap_detection_options" attribute as an array.
     *
     * @param  string  $value
     * @return array
     */
    public function getFlapDetectionOptionsAttribute($value=[])
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
        if($value)
            $this->attributes['flap_detection_options'] = implode(',', $value);
        else
            return [];
    }

    /**
     * Set the value of the "notification_options" attribute as a comma-separated string.
     *
     * @param  mixed  $value
     * @return void
     */
    public function setNotificationOptionsAttribute($value)
    {
        if($value)
            $this->attributes['notification_options'] = implode(',', $value);
        else
          return [];
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

    public function serviceGroup()
    {
        return $this->belongsToMany(ServiceGroup::class, 'service', 'servicegroup')->select('servicegroup_name');
    }
    public function service_contact()
    {
        return $this->belongsToMany(Contact::class, 'service_contact', 'service', 'contact')->select('contact_name');
    }

    public function service_contactgroup()
    {
        // return $this->hasMany(ServiceContactGroup::class, 'service', 'id');
        return $this->belongsToMany(ContactGroup::class, 'service_contactgroup', 'service', 'contactgroup')->select('contactgroup_name');
    }
    public function getServiceContactgroupNamesAttribute()
    {
        $serviceContactGroupNames = $this->service_contactgroup->pluck('contactgroup_name');
        return $serviceContactGroupNames->toArray();
    }
    public function service_servicegroup()
    {
        // return $this->hasMany(ServiceServiceGroup::class, 'servicegroup');
        return $this->belongsToMany(ServiceGroup::class, 'service_servicegroup', 'service', 'servicegroup')->select('servicegroup_name');

    }
    public function file()
    {
        return $this->hasOne(File::class, 'id', 'file_id');
    }
    public function host()
    {
        return $this->hasOne(Host::class, 'id', 'host_name');
    }
    public function command()
    {
        return $this->hasOne(Command::class, 'id', 'check_command');
    }
    public function getFileNameAttribute()
    {
        return optional($this->file)->file_name;
    }
    public function getHostActualNameAttribute()
    {
        return optional($this->host)->host_name;
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
    // public function getMembersAttribute()
    // {
    //     $serviceParentIds = $this->service_servicegroup->pluck('service');
    //     $parenterviceNames = Service::whereIn('id', $serviceParentIds)->pluck('service_description'); // Get an array of parent host names

    //     return $parenterviceNames->toArray();
    // }


    // end of TimePeriod

    // getting the custom variables from the custom_vars table for host only
    public function customVariables()
    {
        return $this->hasMany(CustomVar::class, 'obj_id')->where('obj_type', 'service');
    }
    public function toArray()
    {
        $array = parent::toArray();
        $array['template'] = $this->template_name;
        $array['file_id'] = $this->file_name;
        $array['host_name'] = $this->host_actual_name;
        $array['check_command'] = $this->command_actual_name;
        // for time periods
        $array['notification_period'] = $this->notification_period_actual_name;
        $array['check_period'] = $this->check_period_actual_name;
        $array['service_contactgroup'] = $this->service_contactgroup_names;

        // merging the custom variables
        $customVariables = $this->customVariables->pluck('value', 'variable')->toArray();
        $array['services'] = $this->services;

        $array = array_merge($array, $customVariables);
        return $array;
    }
}
