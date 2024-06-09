<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Contact extends Model
{
    use HasFactory;
    protected $table = "contact";
    // protected $with = ["contact_contactgroup"];
    protected $attributes = [
        'file_id' => 4,
    ];
    //in database there is table called file_tbl /opt/naemon/etc/naemon/conf.d/contacts.cfg

    protected $fillable = [
        'alias',
        'contact_name',
        'file_id',
        'host_notification_options',
        'host_notification_period',
        'service_notification_options',
        'service_notification_period',
        'address1',
        'address2',
        'address3',
        'address4',
        'address5',
        'address6',
        'can_submit_commands',
        'email',
        'host_notification_cmds',
        'host_notification_cmds_args',
        'host_notifications_enabled',
        'pager',
        'register',
        'retain_nonstatus_information',
        'retain_status_information',
        'service_notification_cmds',
        'service_notification_cmds_args',
        'service_notifications_enabled',
        'template',
        'minimum_value',
        'name'
    ];
    public $timestamps = false;
    protected $casts = [
        'host_notification_options' => 'array',
        'service_notification_options' => 'array',
    ];
    /**
     * Get the value of the "host_notification_options" attribute as an array.
     *
     * @param  string  $value
     * @return array
     */
    public function getHostNotificationOptionsAttribute($value)
    {
        if($value)
        {
            return explode(',', $value);
        }
        return [];

    }

    /**
     * Set the value of the "host_notification_options" attribute as a comma-separated string.
     *
     * @param  mixed  $value
     * @return void
     */
    public function setHostNotificationOptionsAttribute($value)
    {

            $this->attributes['host_notification_options'] = implode(',', $value);
    }

    /**
     * Get the value of the "service_notification_options" attribute as an array.
     *
     * @param  string  $value
     * @return array
     */
    public function getServiceNotificationOptionsAttribute($value)
    {
        if($value)
            return explode(',', $value);
        else
            return [];
    }

    /**
     * Set the value of the "service_notification_options" attribute as a comma-separated string.
     *
     * @param  mixed  $value
     * @return void
     */
    public function setServiceNotificationOptionsAttribute($value)
    {
        $this->attributes['service_notification_options'] = implode(',', $value);
    }

    public function contactGroup()
    {
        return $this->belongsToMany(ContactGroup::class, 'contact_contactgroup', 'contact', 'contactgroup')->select('contactgroup_name');
    }
    // public function contact_contactgroup()
    // {
    //     return $this->hasMany(ContactContactGroup::class, 'contactgroup');
    // }
    public function getContactContactGroupNamesAttribute()
    {
        $names = $this->contactGroup->toArray();

        // Extract only the contactgroup_name values
        $groupNames = array_map(function ($group) {
            return $group['contactgroup_name'];
        }, $names);

        return $groupNames;
    }
    public function file()
    {
        return $this->hasOne(File::class, 'id', 'file_id');
    }
    public function serviceNotificationCommand()
    {
        return $this->hasOne(Command::class, 'id', 'service_notification_cmds');
    }
    public function hostNotificationCommand()
    {
        return $this->hasOne(Command::class, 'id', 'host_notification_cmds');
    }

    // get time periods from the model TimePeriod
    public function hostNotificationPeriod()
    {
        return $this->hasOne(TimePeriod::class, 'id', 'host_notification_period');
    }
    public function serviceNotificationPeriod()
    {
        return $this->hasOne(TimePeriod::class, 'id', 'service_notification_period');
    }

    // create a attribute on runtime for the model and later in toArray function append it to the response as string
    // for the service
    public function getServiceTimePeriodActualNameAttribute()
    {
        return optional($this->serviceNotificationPeriod)->timeperiod_name;
    }

    // for host
    public function getHostTimePeriodActualNameAttribute()
    {
        return optional($this->hostNotificationPeriod)->timeperiod_name;
    }


    // end of TimePeriod

    public function getServiceNotificationActualCommandNameAttribute()
    {
        return optional($this->serviceNotificationCommand)->command_name;
    }
    public function getHostNotificationActualCommandNameAttribute()
    {
        return optional($this->hostNotificationCommand)->command_name;
    }


    public function getFileNameAttribute()
    {
        return optional($this->file)->file_name;
    }
    public function getTemplateNameAttribute()
    {
        $template = Contact::where('id',$this->template)->where('register',0)->first();
        return $template ? $template->name : null;
    }

    public function toArray()
    {
        $array = parent::toArray();
        $array['template'] = $this->template_name;
        $array['file_id'] = $this->file_name;
        $array['service_notification_cmds'] = $this->service_notification_actual_command_name;
        $array['host_notification_cmds'] = $this->host_notification_actual_command_name;
        // now append the value of time periods here in response for host and service
        $array['host_notification_period'] = $this->host_time_period_actual_name;
        $array['service_notification_period'] = $this->service_time_period_actual_name;
        $array['contact_contactgroup'] = $this->contact_contact_group_names;

        return $array;
    }
}
