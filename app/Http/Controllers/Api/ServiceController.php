<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use App\Models\Service;
use App\Models\Host;
use App\Models\HostGroup;
use App\Models\ServiceServiceGroup;
use App\Models\ServiceContact;
use App\Models\ServiceContactGroup;
use Illuminate\Support\Facades\URL;
use App\Models\Command;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Arr;
use DB;

class ServiceController extends Controller
{
    // protected $defaultTemplate = 'generic-host';
    protected $defaultFileId = '/opt/naemon/etc/naemon/conf.d/templates/services.cfg';
    protected $defaultStalkingOptions = ['n'];
    protected $defaultRegister = true;
    // protected $defaultCheckCommand = "notify-host-by-email3333";
    // protected $defaultMaxCheckAttempts = 5;
    // protected $defaultCheckInterval = 5;
    // protected $defaultRetryInterval = 1;
    // protected $defaultActiveChecksEnabled = true;
    // protected $defaultPassiveChecksEnabled = true;
    // protected $defaultCheckPeriod = '24x7';
    // protected $defaultEventHandlerEnabled = true;
    // protected $defaultFlapDetectionEnabled = true;
    // protected $defaultProcessPerfData = true;
    // protected $defaultRetainStatusInformation = true;
    // protected $defaultRetainNonStatusInformation = true;
    // protected $defaultNotificationInterval = 0;
    // protected $defaultNotificationPeriod = '24x7';
    protected $defaultNotificationOptions = ['c', 'f', 'r', 's', 'u','w'];
    // protected $defaultNotificationsEnabled = true;
    // protected $defaultFlapDetectionOptions = [];

    /**
     * @OA\Get(
     *      path="/api/config/service/{hostName}/{serviceDescription}",
     *      operationId="getAllServices",
     *      tags={"Services"},
     *      summary="Get all services or single service in Emca Monitor",
     *      description="Retrieves a list of all Services or a specific service by ID or name.",
     *      security={{"basicAuth":{}}},
     *      @OA\Parameter(
     *          name="serviceDescription",
     *          in="path",
     *          description="ID or name of the service (optional)",
     *          @OA\Schema(
     *              type="string",
     *          ),
     *      ),
     *      @OA\Parameter(
     *          name="hostName",
     *          in="path",
     *          description="ID or name of the host (optional)",
     *          @OA\Schema(
     *              type="string",
     *          ),
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="List of services",
     *          @OA\JsonContent(
     *              type="array",
     *              @OA\Items(),
     *          ),
     *      ),
     * )
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getAllServices(Request $request,  $hostName = null, $serviceDescription = null): JsonResponse
    {
        $hostName = urldecode($hostName);
        $serviceDescription = urldecode($serviceDescription);

        if ($hostName) {
            $hostName = Host::where('host_name', $hostName)->pluck('id')->first();
        }

        // Get the identifier from the request
        $identifier = urldecode($serviceDescription);

        // Check if both $identifier and $hostName need to be set to null
        if ($identifier === '{serviceDescription}' || $identifier === '' || $hostName === '{hostName}' || $hostName === '') {
            $identifier = null;
            $hostName = null;
    }

         // Query the Service model with eager loading of ServiceGroup
         $services = Service::query();

         // If an identifier is provided, filter by ID or name
         if ($identifier && $hostName) {
             $services->where('id', $identifier)
                 ->orWhere('service_description', $identifier)->where('host_name',$hostName);
         }

         // Retrieve services
         $services = $services->orderBy('id','desc')->get();

         // Check if services are found
         if ($services->isEmpty()) {
             return response()->json(['error' => 'Service not found'], 404);
         }
         if(!$identifier && !$hostName)
         {
             $responseData = $services->map(function ($service) {
                $getHostName = Host::where('id',$service->host_name)->pluck('host_name')->first();
                 return [
                     'name' => $service->service_description,
                     'resource' => URL::to('/api/config/service/'.urlencode($getHostName).'/'. urlencode($service->service_description))
                 ];
             });
             return response()->json($responseData, 200);
         }

        // Return a JSON response

        $service = $services->first();
        $service->service_group = $service->service_servicegroup()->pluck('servicegroup_name')->toArray();
        $service->service_contact = $service->service_contact()->pluck('contact_name')->toArray();

         return response()->json($service, 200);
    }


    /**
     * @OA\Post(
     *      path="/api/config/service",
     *      operationId="createService",
     *      tags={"Services"},
     *      summary="Create a new service in Emca Monitor",
     *      description="Creates a new service with the provided parameters.",
     *      security={{"basicAuth":{}}},
     *      @OA\RequestBody(
     *          required=true,
     *          description="Service details",
     *          @OA\JsonContent(
     *              required={"max_check_attempts", "host_name", "service_description", "file_id", "notification_interval"},
    *     @OA\Property(property="max_check_attempts", type="integer", format="int32", description="Tells op5 Monitor how many times it should retry a service / host before it changes to HARD.", example=3),
    *     @OA\Property(property="host_name", type="string", description="Identifies a host in op5 Monitor. Many hosts can share the same IP, but no two hosts can have the same host_name.", example="localhost"),
    *     @OA\Property(property="service_description", type="string", description="A description of the service in question. This, together with 'host_name', also identifies the service.", example="Web Server"),
    *     @OA\Property(property="check_command", type="string", description="Reference pointer to command_name. This tells Monitor which command should be run to determine the status of the host or service.", example="notify-service-by-email"),
    *     @OA\Property(property="check_command_args", type="string", description="Arguments that can be passed to the command_name. Some check_commands need arguments and some don't.", example="--ssl"),
    *     @OA\Property(property="obsess", type="boolean", description="If activated, the obsessive-compulsive service processing (OCSP) command will be executed after each check for this service.", example=true),
    *     @OA\Property(property="register", type="boolean", description="If this is set to on, the object is considered to be part of the configuration. Otherwise, it used as a template.", example=true),
    *     @OA\Property(property="file_id", type="string", description="Tells op5 Monitor in which file this object should be stored.", example="/opt/naemon/etc/naemon/conf.d/templates/services.cfg"),
    *     @OA\Property(property="is_volatile", type="boolean", description="Volatile services differ from 'normal' services in three important ways.", example=false),
    *     @OA\Property(property="check_interval", type="integer", format="int32", description="Tells Monitor how often (in minutes) it should check the services.", example=5),
    *     @OA\Property(property="retry_interval", type="integer", format="int32", description="Tells op5 Monitor how many minutes it should wait before retrying a check that is in a non-OK state.", example=2),
    *     @OA\Property(property="active_checks_enabled", type="boolean", description="Tells op5 Monitor whether or not active checks should be performed for this host or service.", example=true),
    *     @OA\Property(property="passive_checks_enabled", type="boolean", description="Tells whether this service accepts passive check results or not.", example=true),
    *     @OA\Property(property="check_period", type="string", description="This directive is used to specify during which time_period active checks for this host could be executed.", example="24x7"),
    *     @OA\Property(property="event_handler_enabled", type="boolean", description="Tells op5 Monitor if event handlers (if configured) should be enabled.", example=true),
    *     @OA\Property(property="flap_detection_enabled", type="boolean", description="If a host or service pends between an OK and a not OK state, the host / service enters 'flapping' state.", example=false),
    *     @OA\Property(property="process_perf_data", type="boolean", description="Tells monitor whether or not it should log processing performance data.", example=true),
    *     @OA\Property(property="retain_status_information", type="boolean", description="Tells op5 Monitor whether or not it should auto-save the service's / host's STATUS information.", example=false),
    *     @OA\Property(property="retain_nonstatus_information", type="boolean", description="Tells op5 Monitor whether or not it should auto-save extra information about the host / service every hour.", example=true),
    *     @OA\Property(property="notification_interval", type="integer", format="int32", description="Time interval (in minutes) between notifications when a host or service is in a non-OK state.", example=10),
    *     @OA\Property(property="notification_period", type="integer", description="Reference to timeperiod_name. Tells monitor during which times notifications should be sent.", example="24x7"),
    *     @OA\Property(property="notification_options", type="array", @OA\Items(type="string", example="c,w")),
    *     @OA\Property(property="notifications_enabled", type="boolean", description="Tells monitor if notifications should be sent out when this host or service changes state.", example=true),
    *     @OA\Property(property="hostgroup_name", type="integer", description="Identifies a hostgroup in op5 Monitor.", example="switches"),
    *     @OA\Property(property="display_name", type="string", description="This directive is used to define an alternate name that should be displayed in the web interface for this object.", example="Web Server 1"),
    *     @OA\Property(property="servicegroups", type="array", description="A list of service groups that this service is a member of.", @OA\Items(type="string", example="service_group-update-again-2")),
    *     @OA\Property(property="parallelize_check", type="boolean", description="Tells op5 Monitor whether the check should be run in parallel with other checks or serial as the only single check being executed at the time.", example=false),
    *     @OA\Property(property="check_freshness", type="boolean", description="This option is used for checking the freshness of a passive service result.", example=true),
    *     @OA\Property(property="freshness_threshold", type="integer", format="int32", description="Freshness threshold for passive checks, in seconds.", example=300),
    *     @OA\Property(property="event_handler", type="integer", description="Defines the command that should be run if event handlers are enabled and a status change just occurred.", example=1),
    *     @OA\Property(property="event_handler_args", type="string", description="Arguments to the event handler script.", example="--arg1 value1"),
    *     @OA\Property(property="low_flap_threshold", type="integer", format="int32", description="Flap detection is used to suppress notifications in case of 'unstable' hosts or services.", example=10),
    *     @OA\Property(property="high_flap_threshold", type="integer", format="int32", description="Flap detection is used to suppress notifications in case of 'flapping' hosts or services.", example=20),
    *     @OA\Property(property="flap_detection_options", type="array", @OA\Items(type="string", example="o")),
    *     @OA\Property(property="first_notification_delay", type="integer", format="int32", description="The number of time units (usually minutes) to block notifications after a non-OK (possibly soft) state.", example=5),
    *     @OA\Property(property="contacts", type="array", description="This is a list of the contacts that should be notified whenever there are problems (or recoveries) with this object.", @OA\Items(type="string", example="admin1")),
    *     @OA\Property(property="contact_groups", type="array", description="List of references to contactgroup_name. Tells monitor which contacts should receive notifications when the service / host in question changes state.", @OA\Items(type="string", example="IT Support")),
    *     @OA\Property(property="stalking_options", type="array", @OA\Items(type="string", example="n")),
    *     @OA\Property(property="notes", type="string", description="A more descriptive information for the host or service.", example="This is a critical service."),
    *     @OA\Property(property="notes_url", type="string", description="A URL of your own choice.", example="https://example.com/service_notes"),
    *     @OA\Property(property="action_url", type="string", description="Define an optional URL that can be used to provide more actions on the host.", example="https://example.com/actions"),
    *     @OA\Property(property="icon_image", type="string", description="Defines the icon that will be used at status pages in the web GUI.", example="icon.png"),
    *     @OA\Property(property="icon_image_alt", type="string", description="Defines the alt text that appears when you put the cursor over the icon.", example="Service Icon"),
     *
     *          ),
     *      ),
     *      @OA\Response(
     *          response=201,
     *          description="Service created successfully",
     *          @OA\JsonContent(
     *              @OA\Property(property="message", type="string", example="Service created successfully"),
     *              @OA\Property(property="service", type="object"),
     *          ),
     *      ),
     *      @OA\Response(
     *          response=400,
     *          description="Bad request",
     *          @OA\JsonContent(
     *              @OA\Property(property="message", type="string", example="Validation error"),
     *              @OA\Property(property="errors", type="object", example={"service_description": {"The service_description field is required."}}),
     *          ),
     *      ),
     * )
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function createService(Request $request): JsonResponse
    {
        $register = $request->has('register') ? $request->input('register') : $this->defaultRegister;

            // Merge the modified request with the default value
            $request->merge(['register' => $register]);


        // Additional required rules for creation
        $creationRequiredRules = [
            'max_check_attempts' => 'required|integer',
            'host_name' => 'nullable|string',
            'service_description' => 'required|string|unique:service',
            'notification_interval' => 'required|integer',
            'file_id' => 'nullable|string',
        ];

        // Merge the common rules with creation rules
        $creationRules = array_merge(Service::$rules, $creationRequiredRules);

        // Create the validator with merged rules
        $validator = Validator::make($request->all(), $creationRules);

        // check if the service is already there for same host

            $host = Host::where('id', $request->host_name)
            ->orWhere('host_name', $request->host_name)
            ->first();
        $command = Command::where('id', $request->check_command)->orWhere('command_name',"=", $request->check_command)->pluck('id')->first();
        // if (!$host) {
        //     return response()->json(['message' => 'Host not found'], 404);
        // }
        // unset($request['host_name']);
        // unset($request['check_command']);
        if(!$command)
        {
            return response()->json(['message' => 'command not found'], 404);
        }
        if($host && $request->service_description)
        {
            $ifServiceExist = Service::where('host_name',$host->id)->where('service_description', $request->service_description)->first();
            if($ifServiceExist && $request->register)
            {
                return response()->json(['message' => 'duplicate Service'], 409);
            }
        }

        // Merge the common rules with creation rules
        $creationRules = array_merge(Service::$rules, $creationRequiredRules);

        // Create the validator with merged rules
        $validator = Validator::make($request->all(), $creationRules);

        // check if the service is already there for same host
        $host = Host::where('id', $request->host_name)
            ->orWhere('host_name', $request->host_name)
            ->first();
        if (!$host) {
            return response()->json(['message' => 'Host not found'], 404);
        }
        unset($request['host_name']);

        $ifServiceExist = Service::where('host_name',$host->id)->where('service_description', $request->service_description)->first();
        if($ifServiceExist)
        {
            return response()->json(['message' => 'duplicate Service'], 409);
        }
        // Create a new service using the Service model
        $template = Service::where('name', $request->template)->where('register',0)->pluck('id')->first();
        if (!$template && !$request->register) {
            return response()->json(['message' => 'Template not found'], 404);
        }
        // unset($request['template']);
        // $service = Service::create($request->all());

        if($request->file_id)
        {
            $fileId = \DB::table('file_tbl')->select('id')->where('file_name', $request->file_id)->pluck('id')->first();
            // if (!$fileId) {
            //     return response()->json(['message' => 'file id not found'], 404);
            // }
        }

        if( $request->has('notification_period'))
        {
            $notificationPeriod = \DB::table('timeperiod')->where('timeperiod_name', $request->notification_period)->pluck('id')->first();
            if (!$notificationPeriod) {
                return response()->json(['message' => 'notification_period  not found'], 404);
            }
        }

        if($request->has('contact_groups'))
        {
            $contactGroups = \DB::table('contactgroup')->whereIn('contactgroup_name', $request->contact_groups)->pluck('id');
        }

        if($request->has('servicegroups'))
        {
            $serviceGroups = \DB::table('servicegroup')->whereIn('servicegroup_name', $request->servicegroups)->pluck('id');
        }
        if($request->has('hostgroup_name'))
        {
            $hostGroupName = \DB::table('hostgroup')->select('id')->where('hostgroup_name', $request->hostgroup_name)->first();
        }


        if($request->has('contacts'))
        {
            $contacts = \DB::table('contact')->whereIn('contact_name', $request->contacts)->pluck('id');
        }
        if($request->has('check_command'))
        {
            $checkCommand = \DB::table('command')->where('command_name', $request->check_command)->pluck('id')->first();
        }
        if( $request->has('check_period'))
        {
            $checkPeriod = \DB::table('timeperiod')->where('timeperiod_name', $request->check_period)->pluck('id')->first();
            if (!$checkPeriod) {
                return response()->json(['message' => 'check_period  not found'], 404);
            }
        }

        // Assign values to each property if they are set in the request
        $service = new Service();
        $service->check_command = isset($command) ? $command : null;
        $service->check_command_args = isset($request->check_command_args) ? $request->check_command_args : null;
        $service->obsess = isset($request->obsess) ? $request->obsess : null;
        $service->template = isset($template) ?  $template : null;
        $service->register = isset($request->register) ? $request->register : true;
        $service->is_volatile = isset($request->is_volatile) ? $request->is_volatile : null;
        $service->max_check_attempts = isset($request->max_check_attempts) ? $request->max_check_attempts : null;
        $service->check_interval = isset($request->check_interval) ? $request->check_interval : null;
        $service->retry_interval = isset($request->retry_interval) ? $request->retry_interval : null;
        $service->active_checks_enabled = isset($request->active_checks_enabled) ? $request->active_checks_enabled : null;
        $service->passive_checks_enabled = isset($request->passive_checks_enabled) ? $request->passive_checks_enabled : null;
        $service->check_period = isset($checkPeriod) ? $checkPeriod : null;
        $service->event_handler_enabled = isset($request->event_handler_enabled) ? $request->event_handler_enabled : null;
        $service->flap_detection_enabled = isset($request->flap_detection_enabled) ? $request->flap_detection_enabled : null;
        $service->process_perf_data = isset($request->process_perf_data) ? $request->process_perf_data : null;
        $service->retain_status_information = isset($request->retain_status_information) ? $request->retain_status_information : null;
        $service->retain_nonstatus_information = isset($request->retain_nonstatus_information) ? $request->retain_nonstatus_information : null;
        $service->notification_period = isset($notificationPeriod) ? $notificationPeriod : null;
        $service->notification_options = isset($request->notification_options) ? $request->notification_options : $this->defaultNotificationOptions;
        $service->notifications_enabled = isset($request->notifications_enabled) ? $request->notifications_enabled : null;
        $service->display_name = isset($request->display_name) ? $request->display_name : null;
        $service->parallelize_check = isset($request->parallelize_check) ? $request->parallelize_check : null;
        $service->check_freshness = isset($request->check_freshness) ? $request->check_freshness : null;
        $service->freshness_threshold = isset($request->freshness_threshold) ? $request->freshness_threshold : null;
        $service->event_handler = isset($request->event_handler) ? $request->event_handler : null;
        $service->event_handler_args = isset($request->event_handler_args) ? $request->event_handler_args : null;
        $service->low_flap_threshold = isset($request->low_flap_threshold) ? $request->low_flap_threshold : null;
        $service->high_flap_threshold = isset($request->high_flap_threshold) ? $request->high_flap_threshold : null;
        $service->flap_detection_options = isset($request->flap_detection_options) ? $request->flap_detection_options : null;
        $service->first_notification_delay = isset($request->first_notification_delay) ? $request->first_notification_delay : null;
        $service->stalking_options = isset($request->stalking_options) ? $request->stalking_options : $this->defaultStalkingOptions;
        $service->notes = isset($request->notes) ? $request->notes : null;
        $service->notes_url = isset($request->notes_url) ? $request->notes_url : null;
        $service->action_url = isset($request->action_url) ? $request->action_url : null;
        $service->icon_image = isset($request->icon_image) ? $request->icon_image : null;
        $service->icon_image_alt = isset($request->icon_image_alt) ? $request->icon_image_alt : null;
        $service->name = isset($request->name) ? $request->name : null;

        $service->host_name = isset($host) ? $host->id : null;
        $service->hostgroup_name = isset($hostGroupName) ? $hostGroupName->id : null;


        $service->service_description = isset($request->service_description) ? $request->service_description : null;
        $service->notification_interval = isset($request->notification_interval) ? $request->notification_interval : null;
        $service->file_id = isset($fileId) ? $fileId : $this->getDefaultFileId();
        if($service->save())
        {
                /*
                    * creating host_contactgroup as contact_groups
                    List of references to contactgroup_name. Tells monitor which contacts
                    should receive notifications when the service / host in question changes state.

                */
                if(isset($contactGroups))
                {
                    foreach($contactGroups as $contactGroup)
                    {
                        $newServiceContactGroup = new ServiceContactGroup();
                        $newServiceContactGroup->contactgroup = $contactGroup;
                        $newServiceContactGroup->service = $service->id;
                        $newServiceContactGroup->save();

                    }
                }


                /*
                    * creating servicegroup
                    A list of service groups that this service is a member of.

                */
                if(isset($serviceGroups))
                {
                    foreach($serviceGroups as $serviceGroup)
                {
                    $newServiceServiceGroup = new ServiceServiceGroup();
                    $newServiceServiceGroup->servicegroup = $serviceGroup;
                    $newServiceServiceGroup->service = $service->id;
                    $newServiceServiceGroup->save();

                }
                }


                /*
                    * creating service contacts
                    This is a list of the contacts that should be notified whenever there are problems (or recoveries) with this object.
                    Useful if you want notifications to go to just a few people and don't want to configure contact groups.
                */
                if(isset($contacts))
                {
                    foreach($contacts as $contact)
                {
                    $newServiceServiceGroup = new ServiceContact();
                    $newServiceServiceGroup->contact = $contact;
                    $newServiceServiceGroup->service = $service->id;
                    $newServiceServiceGroup->save();
                }
                }
                // using the laravel Arr utility class to manipulate arrays efficiently
                $custom_variables = Arr::where($request->all(), function ($value, $key) {
                    return strpos($key, '_') === 0;
                });
                // Convert the array to a collection
                $custom_variables_collection = collect($custom_variables);

                // If there are custom variables, insert them into the database
                if ($custom_variables_collection->isNotEmpty()) {
                    // Prepare the data for bulk insert
                    $data = $custom_variables_collection->map(function ($value, $key) use ($service) {
                        return [
                            'obj_type' => 'service',
                            'obj_id' => $service->id,
                            'variable' => $key,
                            'value' => $value,
                        ];
                    })->toArray();

                    // Bulk insert the data
                    DB::table('custom_vars')->insert($data);
                }



                // Return a JSON response with a 201 Created status code
                return response()->json([
                    'message' => 'Service created successfully',
                    'service' => $service,
                ], 201);
        }

        // Update the service attributes
    //    if($request->register)
    //    {
    //     $service->host_name = $host->id;
    //    }
        // // Create a new host using the Host model
        if($request->register)
        {
            $service->template = isset($template) ?  $template : null;
            $service->save();
        }



        if($request->file_id)
        {
            $fileId = \DB::table('file_tbl')->select('id')->where('file_name', $request->file_id)->first();
            if ($fileId) {
                $service->file_id = $fileId->id;
                unset($request['file_id']);
            }
        }
        // Update the service attributes
        $service->host_name = $host->id;
        $service->save();


        // Return a JSON response with a 201 Created status code
        return response()->json([
            'message' => 'Service created successfully',
            'service' => $service,
        ], 201);
    }
    /**
    * @OA\Put(
    *      path="/api/config/service/{resource};{service}",
    *      operationId="updateService",
    *      tags={"Services"},
    *      summary="Update a service in Emca Monitor",
    *      description="Updates an existing service with the provided parameters.",
    *      security={{"basicAuth":{}}},
    *      @OA\Parameter(
    *          name="service",
    *          in="path",
    *          required=true,
    *          description="ID or Service name, of the service to be updated",
    *          @OA\Schema(type="string")
    *      ),
    *      @OA\Parameter(
    *          name="resource",
    *          in="path",
    *          description="Host or Hostgroup",
    *          required=true,
    *          @OA\Schema(type="string")
    *      ),
    *      @OA\Parameter(
    *          name="parent_type",
    *          in="query",
    *          required=false,
    *          description="Type of the parent (hostgroup)",
    *          @OA\Schema(type="string", enum={"hostgroup"})
    *      ),
    *      @OA\RequestBody(
    *          required=true,
    *          description="Service details",
    *          @OA\JsonContent(
    *             *              required={"max_check_attempts", "host_name", "service_description", "file_id", "notification_interval"},
    *     @OA\Property(property="max_check_attempts", type="integer", format="int32", description="Tells op5 Monitor how many times it should retry a service / host before it changes to HARD.", example=3),
    *     @OA\Property(property="host_name", type="integer", format="int32", description="Identifies a host in op5 Monitor. Many hosts can share the same IP, but no two hosts can have the same host_name.", example=123),
    *     @OA\Property(property="service_description", type="string", description="A description of the service in question. This, together with 'host_name', also identifies the service.", example="Web Server"),
    *     @OA\Property(property="check_command", type="integer", description="Reference pointer to command_name. This tells Monitor which command should be run to determine the status of the host or service.", example="notify-service-by-email"),
    *     @OA\Property(property="check_command_args", type="string", description="Arguments that can be passed to the command_name. Some check_commands need arguments and some don't.", example="--ssl"),
    *     @OA\Property(property="obsess", type="boolean", description="If activated, the obsessive-compulsive service processing (OCSP) command will be executed after each check for this service.", example=true),
    *     @OA\Property(property="register", type="boolean", description="If this is set to on, the object is considered to be part of the configuration. Otherwise, it used as a template.", example=true),
    *     @OA\Property(property="file_id", type="string", description="Tells op5 Monitor in which file this object should be stored.", example="/opt/naemon/etc/naemon/conf.d/templates/services.cfg"),
    *     @OA\Property(property="is_volatile", type="boolean", description="Volatile services differ from 'normal' services in three important ways.", example=false),
    *     @OA\Property(property="check_interval", type="integer", format="int32", description="Tells Monitor how often (in minutes) it should check the services.", example=5),
    *     @OA\Property(property="retry_interval", type="integer", format="int32", description="Tells op5 Monitor how many minutes it should wait before retrying a check that is in a non-OK state.", example=2),
    *     @OA\Property(property="active_checks_enabled", type="boolean", description="Tells op5 Monitor whether or not active checks should be performed for this host or service.", example=true),
    *     @OA\Property(property="passive_checks_enabled", type="boolean", description="Tells whether this service accepts passive check results or not.", example=true),
    *     @OA\Property(property="check_period", type="string", description="This directive is used to specify during which time_period active checks for this host could be executed.", example="24x7"),
    *     @OA\Property(property="event_handler_enabled", type="boolean", description="Tells op5 Monitor if event handlers (if configured) should be enabled.", example=true),
    *     @OA\Property(property="flap_detection_enabled", type="boolean", description="If a host or service pends between an OK and a not OK state, the host / service enters 'flapping' state.", example=false),
    *     @OA\Property(property="process_perf_data", type="boolean", description="Tells monitor whether or not it should log processing performance data.", example=true),
    *     @OA\Property(property="retain_status_information", type="boolean", description="Tells op5 Monitor whether or not it should auto-save the service's / host's STATUS information.", example=false),
    *     @OA\Property(property="retain_nonstatus_information", type="boolean", description="Tells op5 Monitor whether or not it should auto-save extra information about the host / service every hour.", example=true),
    *     @OA\Property(property="notification_interval", type="integer", format="int32", description="Time interval (in minutes) between notifications when a host or service is in a non-OK state.", example=10),
    *     @OA\Property(property="notification_period", type="integer", description="Reference to timeperiod_name. Tells monitor during which times notifications should be sent.", example="24x7"),
    *     @OA\Property(property="notification_options", type="array", @OA\Items(type="string", example="c,w")),
    *     @OA\Property(property="notifications_enabled", type="boolean", description="Tells monitor if notifications should be sent out when this host or service changes state.", example=true),
    *     @OA\Property(property="hostgroup_name", type="integer", description="Identifies a hostgroup in op5 Monitor.", example="1"),
    *     @OA\Property(property="display_name", type="string", description="This directive is used to define an alternate name that should be displayed in the web interface for this object.", example="Web Server 1"),
    *     @OA\Property(property="servicegroups", type="array", description="A list of service groups that this service is a member of.", @OA\Items(type="string", example="service_group-update-again-2")),
    *     @OA\Property(property="parallelize_check", type="boolean", description="Tells op5 Monitor whether the check should be run in parallel with other checks or serial as the only single check being executed at the time.", example=false),
    *     @OA\Property(property="check_freshness", type="boolean", description="This option is used for checking the freshness of a passive service result.", example=true),
    *     @OA\Property(property="freshness_threshold", type="integer", format="int32", description="Freshness threshold for passive checks, in seconds.", example=300),
    *     @OA\Property(property="event_handler", type="integer", description="Defines the command that should be run if event handlers are enabled and a status change just occurred.", example=1),
    *     @OA\Property(property="event_handler_args", type="string", description="Arguments to the event handler script.", example="--arg1 value1"),
    *     @OA\Property(property="low_flap_threshold", type="integer", format="int32", description="Flap detection is used to suppress notifications in case of 'unstable' hosts or services.", example=10),
    *     @OA\Property(property="high_flap_threshold", type="integer", format="int32", description="Flap detection is used to suppress notifications in case of 'flapping' hosts or services.", example=20),
    *     @OA\Property(property="flap_detection_options", type="array", @OA\Items(type="string", example="OK")),
    *     @OA\Property(property="first_notification_delay", type="integer", format="int32", description="The number of time units (usually minutes) to block notifications after a non-OK (possibly soft) state.", example=5),
    *     @OA\Property(property="contacts", type="array", description="This is a list of the contacts that should be notified whenever there are problems (or recoveries) with this object.", @OA\Items(type="string", example="admin1")),
    *     @OA\Property(property="contact_groups", type="array", description="List of references to contactgroup_name. Tells monitor which contacts should receive notifications when the service / host in question changes state.", @OA\Items(type="string", example="IT Support")),
    *     @OA\Property(property="stalking_options", type="array", @OA\Items(type="string", example="n")),
    *     @OA\Property(property="notes", type="string", description="A more descriptive information for the host or service.", example="This is a critical service."),
    *     @OA\Property(property="notes_url", type="string", description="A URL of your own choice.", example="https://example.com/service_notes"),
    *     @OA\Property(property="action_url", type="string", description="Define an optional URL that can be used to provide more actions on the host.", example="https://example.com/actions"),
    *     @OA\Property(property="icon_image", type="string", description="Defines the icon that will be used at status pages in the web GUI.", example="icon.png"),
    *     @OA\Property(property="icon_image_alt", type="string", description="Defines the alt text that appears when you put the cursor over the icon.", example="Service Icon"),
     *          ),
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Service updated successfully",
     *          @OA\JsonContent(
     *              @OA\Property(property="message", type="string", example="Service updated successfully"),
     *              @OA\Property(property="service", type="object"),
     *          ),
     *      ),
     *      @OA\Response(
     *          response=400,
     *          description="Bad request",
     *          @OA\JsonContent(
     *              @OA\Property(property="message", type="string", example="Validation error"),
     *              @OA\Property(property="errors", type="object"),
     *          ),
     *      ),
     *      @OA\Response(
     *          response=404,
     *          description="Service not found",
     *          @OA\JsonContent(
     *              @OA\Property(property="message", type="string", example="Service not found"),
     *          ),
     *      ),
     * )
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function updateService(Request $request, $params): JsonResponse
    {
        $register = $request->has('register') ? $request->input('register') : true;

        // Merge the modified request with the default value
        $request->merge(['register' => $register]);


        $params = explode(';', $params);
        $service = urldecode($params[1]);
        $resource    = urldecode($params[0]);

        // Validate the incoming request data
        $validator = Validator::make($request->all(), Service::$rules);



        if($request->parent_type)
        {
            $hostGroup = HostGroup::where('id', $resource)
            ->orWhere('hostgroup_name', $resource)
            ->first();
        }
        else
        {
            $host = Host::where('id', $resource)
            ->orWhere('host_name', $resource)
            ->where('register',1)
            ->first();
        }
        // if(!$hostGroup)
        // {
        //     return response()->json(['message' => 'Host Group not found'], 404);
        // }

        // Find the service by ID or name
        $getServicesQuery = Service::where('service_description', $service);

        if (isset($hostGroup)) {
            $getServicesQuery->Where('hostgroup_name', $hostGroup->id);
        }
        $host = Host::where('id', $request->host_name)
            ->orWhere('host_name', $request->host_name)
            ->first();
        if (!$host) {
            return response()->json(['message' => 'Host not found'], 404);
        }
        unset($request['host_name']);

        if (isset($host)) {
            $getServicesQuery->Where('host_name', $host->id);
        }

        /******************************************************* */

            if( $request->has('notification_period'))
            {
                $notificationPeriod = \DB::table('timeperiod')->where('timeperiod_name', $request->notification_period)->pluck('id')->first();
                if (!$notificationPeriod) {
                    return response()->json(['message' => 'notification_period  not found'], 404);
                }
            }

            if($request->has('contact_groups'))
            {
                $contactGroups = \DB::table('contactgroup')->whereIn('contactgroup_name', $request->contact_groups)->pluck('id');
            }

            if($request->has('servicegroups'))
            {
                $serviceGroups = \DB::table('servicegroup')->whereIn('servicegroup_name', $request->servicegroups)->pluck('id');
            }
            if($request->has('hostgroup_name'))
            {
                $hostGroupName = \DB::table('hostgroup')->select('id')->where('hostgroup_name', $request->hostgroup_name)->first();
            }


            if($request->has('contacts'))
            {
                $contacts = \DB::table('contact')->whereIn('contact_name', $request->contacts)->pluck('id');
            }
            if($request->has('check_command'))
            {
                $checkCommand = \DB::table('command')->where('command_name', $request->check_command)->pluck('id')->first();
            }
            if( $request->has('check_period'))
            {
                $checkPeriod = \DB::table('timeperiod')->where('timeperiod_name', $request->check_period)->pluck('id')->first();
                if (!$checkPeriod) {
                    return response()->json(['message' => 'check_period  not found'], 404);
                }
            }
            $template = Service::where('name', $request->template)->where('register',0)->pluck('id')->first();
            if (!$template && !$request->register) {
                return response()->json(['message' => 'Template not found'], 404);
            }
            if($host && $request->service_description)
            {
                $ifServiceExist = Service::where('host_name',$host->id)->where('service_description', $request->service_description)->first();
                if($ifServiceExist && $request->register)
                {
                    return response()->json(['message' => 'duplicate Service'], 409);
                }
            }


        /******************************************************* */
        $services = $getServicesQuery->get();
        if($services->count() > 0)
        {
            foreach($services as $service)
            {
                // Check if the service exists
                if (!$service) {
                    return response()->json(['message' => 'Service not found'], 404);
                }
                // unset($request['hostgroup']);

                // check if the service is already there for same host
                $query = Service::where('service_description', $request->service_description);

                if (isset($hostGroup)) {
                    $query->where('hostgroup_name', $hostGroup->id);
                }

                if (isset($host)) {
                    $query->Where('host_name', $host->id);
                }

                $ifServiceExist = $query->first();

                if($ifServiceExist && $request->register && isset($host))
                {
                    return response()->json(['message' => 'duplicate Service'], 409);
                }

                // Get the field_id from the file_tbl table based on the provided host identifier
                if($request->file_id)
                {
                    $fileId = \DB::table('file_tbl')->select('id')->where('file_name', $request->file_id)->first();
                    if ($fileId) {
                        $service->file_id = $fileId->id;
                        unset($request['file_id']);
                    }
                }
                $command = Command::where('id', $request->check_command)->orWhere('command_name',"=", $request->check_command)->first();
                unset($request['check_command']);
                // Update the service attributes
                if($request->register && isset($host))
                {
                    $service->host_name = $host->id;
                }
                if($command)
                {
                    $service->check_command = isset($command->id) ? $command->id : null;
                    $service->save();
                }

                // Update the service attributes
                $service->service_description = isset($request->service_description) ? $request->service_description : $service->service_description;
                $service->max_check_attempts = $request->has('max_check_attempts') ? $request->max_check_attempts : $service->max_check_attempts;
                $service->notification_interval = $request->has('notification_interval') ? $request->notification_interval : $service->notification_interval;
                $service->notification_options = $request->has('notification_options') ? $request->notification_options : $service->notification_options;
                $service->notification_period = $request->has('notification_period') ? $notificationPeriod : $service->notification_period;
                $service->check_period = $request->has('check_period') ? $checkPeriod : $service->check_period;
                // Handle nullable fields (assuming they exist in your model)
                $service->template = ($request->has('template') && $request->template !="" && $request->template =null) ? $template : $service->template;
                $service->action_url = isset($validatedData['action_url']) ? $validatedData['action_url'] : $service->action_url;
                $service->active_checks_enabled = isset($validatedData['active_checks_enabled']) ? $validatedData['active_checks_enabled'] : $service->active_checks_enabled;
                $service->check_command = $request->has('check_command') ? $checkCommand : $service->check_command;

                $service->check_command_args = isset($validatedData['check_command_args']) ? $validatedData['check_command_args'] : $service->check_command_args;
                $service->check_freshness = isset($validatedData['check_freshness']) ? $validatedData['check_freshness'] : $service->check_freshness;
                $service->check_interval = isset($validatedData['check_interval']) ? $validatedData['check_interval'] : $service->check_interval;
                // $service->children = isset($validatedData['children']) ? $validatedData['children'] : null;
                $service->display_name = isset($validatedData['display_name']) ? $validatedData['display_name'] : $service->display_name;
                $service->event_handler = isset($validatedData['event_handler']) ? $validatedData['event_handler'] : $service->event_handler;
                $service->event_handler_args = isset($validatedData['event_handler_args']) ? $validatedData['event_handler_args'] : $service->event_handler_args;
                $service->event_handler_enabled = isset($validatedData['event_handler_enabled']) ? $validatedData['event_handler_enabled'] : $service->event_handler_enabled;
                $service->first_notification_delay = isset($validatedData['first_notification_delay']) ? $validatedData['first_notification_delay'] : $service->first_notification_delay;
                $service->flap_detection_enabled = isset($validatedData['flap_detection_enabled']) ? $validatedData['flap_detection_enabled'] : $service->flap_detection_enabled;
                $service->flap_detection_options = isset($validatedData['flap_detection_options']) ? $validatedData['flap_detection_options'] : $service->flap_detection_options;
                $service->freshness_threshold = isset($validatedData['freshness_threshold']) ? $validatedData['freshness_threshold'] : $service->freshness_threshold;
                $service->high_flap_threshold = isset($validatedData['high_flap_threshold']) ? $validatedData['high_flap_threshold'] : $service->high_flap_threshold;
                $service->icon_image = isset($validatedData['icon_image']) ? $validatedData['icon_image'] : $service->icon_image;
                $service->icon_image_alt = isset($validatedData['icon_image_alt']) ? $validatedData['icon_image_alt'] : $service->icon_image_alt;
                $service->low_flap_threshold = isset($validatedData['low_flap_threshold']) ? $validatedData['low_flap_threshold'] : $service->low_flap_threshold;
                $service->notes = isset($validatedData['notes']) ? $validatedData['notes'] : $service->notes;
                $service->notes_url = isset($validatedData['notes_url']) ? $validatedData['notes_url'] : $service->notes_url;
                $service->notifications_enabled = isset($validatedData['notifications_enabled']) ? $validatedData['notifications_enabled'] : $service->notifications_enabled;
                $service->obsess = isset($validatedData['obsess']) ? $validatedData['obsess'] : $service->obsess;
                $service->register = isset($validatedData['register']) ? $validatedData['register'] : $service->register;
                $service->passive_checks_enabled = isset($validatedData['passive_checks_enabled']) ? $validatedData['passive_checks_enabled'] : $service->passive_checks_enabled;
                $service->process_perf_data = isset($validatedData['process_perf_data']) ? $validatedData['process_perf_data'] : $service->process_perf_data;
                $service->retain_nonstatus_information = isset($validatedData['retain_nonstatus_information']) ? $validatedData['retain_nonstatus_information'] : $service->retain_nonstatus_information;
                $service->retain_status_information = isset($validatedData['retain_status_information']) ? $validatedData['retain_status_information'] : $service->retain_status_information;
                $service->retry_interval = isset($validatedData['retry_interval']) ? $validatedData['retry_interval'] : $service->retry_interval;
                $service->stalking_options = isset($validatedData['stalking_options']) ? $validatedData['stalking_options'] : $service->stalking_options;
                $service->name = isset($validatedData['name']) ? $validatedData['name'] : $service->name;
                $service->is_volatile = $request->has('is_volatile') ? $request->is_volatile : $service->is_volatile;
                $service->parallelize_check = isset($request->parallelize_check) ? $request->parallelize_check : $service->parallelize_check;

                if($service->save())
                {
                        /*
                            * creating host_contactgroup as contact_groups
                            List of references to contactgroup_name. Tells monitor which contacts
                            should receive notifications when the service / host in question changes state.

                        */
                        if(isset($contactGroups))
                        {
                            foreach($contactGroups as $contactGroup)
                            {
                                $newServiceContactGroup = new ServiceContactGroup();
                                $newServiceContactGroup->contactgroup = $contactGroup;
                                $newServiceContactGroup->service = $service->id;
                                $newServiceContactGroup->save();

                            }
                        }


                        /*
                            * creating servicegroup
                            A list of service groups that this service is a member of.

                        */
                        if(isset($serviceGroups))
                        {
                            foreach($serviceGroups as $serviceGroup)
                        {
                            $newServiceServiceGroup = new ServiceServiceGroup();
                            $newServiceServiceGroup->servicegroup = $serviceGroup;
                            $newServiceServiceGroup->service = $service->id;
                            $newServiceServiceGroup->save();

                        }
                        }


                        /*
                            * creating service contacts
                            This is a list of the contacts that should be notified whenever there are problems (or recoveries) with this object.
                            Useful if you want notifications to go to just a few people and don't want to configure contact groups.
                        */
                        if(isset($contacts))
                        {
                            foreach($contacts as $contact)
                        {
                            $newServiceServiceGroup = new ServiceContact();
                            $newServiceServiceGroup->contact = $contact;
                            $newServiceServiceGroup->service = $service->id;
                            $newServiceServiceGroup->save();
                        }
                        }
                }

                $this->updateCustomVariables($request, $service);
            }
            // Return a JSON response with a 200 OK status code
            return response()->json([
                'message' => 'Service updated successfully',
                'services' => $services,
            ], 200);

        }
        // Return a JSON response with a 200 OK status code
        return response()->json([
            'message' => 'Service not found',
            'services' => $services,
        ], 200);





    }

    /**
    * @OA\PATCH(
    *      path="/api/config/service/{resource};{service}",
    *      operationId="patchService",
    *      tags={"Services"},
    *      summary="Update a service in Emca Monitor",
    *      description="Updates an existing service with the provided parameters.",
    *      security={{"basicAuth":{}}},
    *      @OA\Parameter(
    *          name="service",
    *          in="path",
    *          required=true,
    *          description="ID or Service name, of the service to be updated",
    *          @OA\Schema(type="string")
    *      ),
    *      @OA\Parameter(
    *          name="resource",
    *          in="path",
    *          description="Host or Hostgroup",
    *          required=true,
    *          @OA\Schema(type="string")
    *      ),
    *      @OA\Parameter(
    *          name="parent_type",
    *          in="query",
    *          required=false,
    *          description="Type of the parent (hostgroup)",
    *          @OA\Schema(type="string", enum={"hostgroup"})
    *      ),
    *      @OA\RequestBody(
    *          required=true,
    *          description="Service details",
    *          @OA\JsonContent(
    *             *              required={"max_check_attempts", "host_name", "service_description", "file_id", "notification_interval"},
    *     @OA\Property(property="max_check_attempts", type="integer", format="int32", description="Tells op5 Monitor how many times it should retry a service / host before it changes to HARD.", example=3),
    *     @OA\Property(property="host_name", type="integer", format="int32", description="Identifies a host in op5 Monitor. Many hosts can share the same IP, but no two hosts can have the same host_name.", example=123),
    *     @OA\Property(property="service_description", type="string", description="A description of the service in question. This, together with 'host_name', also identifies the service.", example="Web Server"),
    *     @OA\Property(property="check_command", type="integer", description="Reference pointer to command_name. This tells Monitor which command should be run to determine the status of the host or service.", example="notify-service-by-email"),
    *     @OA\Property(property="check_command_args", type="string", description="Arguments that can be passed to the command_name. Some check_commands need arguments and some don't.", example="--ssl"),
    *     @OA\Property(property="obsess", type="boolean", description="If activated, the obsessive-compulsive service processing (OCSP) command will be executed after each check for this service.", example=true),
    *     @OA\Property(property="register", type="boolean", description="If this is set to on, the object is considered to be part of the configuration. Otherwise, it used as a template.", example=true),
    *     @OA\Property(property="file_id", type="string", description="Tells op5 Monitor in which file this object should be stored.", example="/opt/naemon/etc/naemon/conf.d/templates/services.cfg"),
    *     @OA\Property(property="is_volatile", type="boolean", description="Volatile services differ from 'normal' services in three important ways.", example=false),
    *     @OA\Property(property="check_interval", type="integer", format="int32", description="Tells Monitor how often (in minutes) it should check the services.", example=5),
    *     @OA\Property(property="retry_interval", type="integer", format="int32", description="Tells op5 Monitor how many minutes it should wait before retrying a check that is in a non-OK state.", example=2),
    *     @OA\Property(property="active_checks_enabled", type="boolean", description="Tells op5 Monitor whether or not active checks should be performed for this host or service.", example=true),
    *     @OA\Property(property="passive_checks_enabled", type="boolean", description="Tells whether this service accepts passive check results or not.", example=true),
    *     @OA\Property(property="check_period", type="string", description="This directive is used to specify during which time_period active checks for this host could be executed.", example="24x7"),
    *     @OA\Property(property="event_handler_enabled", type="boolean", description="Tells op5 Monitor if event handlers (if configured) should be enabled.", example=true),
    *     @OA\Property(property="flap_detection_enabled", type="boolean", description="If a host or service pends between an OK and a not OK state, the host / service enters 'flapping' state.", example=false),
    *     @OA\Property(property="process_perf_data", type="boolean", description="Tells monitor whether or not it should log processing performance data.", example=true),
    *     @OA\Property(property="retain_status_information", type="boolean", description="Tells op5 Monitor whether or not it should auto-save the service's / host's STATUS information.", example=false),
    *     @OA\Property(property="retain_nonstatus_information", type="boolean", description="Tells op5 Monitor whether or not it should auto-save extra information about the host / service every hour.", example=true),
    *     @OA\Property(property="notification_interval", type="integer", format="int32", description="Time interval (in minutes) between notifications when a host or service is in a non-OK state.", example=10),
    *     @OA\Property(property="notification_period", type="integer", description="Reference to timeperiod_name. Tells monitor during which times notifications should be sent.", example="24x7"),
    *     @OA\Property(property="notification_options", type="array", @OA\Items(type="string", example="c,w")),
    *     @OA\Property(property="notifications_enabled", type="boolean", description="Tells monitor if notifications should be sent out when this host or service changes state.", example=true),
    *     @OA\Property(property="hostgroup_name", type="integer", description="Identifies a hostgroup in op5 Monitor.", example="1"),
    *     @OA\Property(property="display_name", type="string", description="This directive is used to define an alternate name that should be displayed in the web interface for this object.", example="Web Server 1"),
    *     @OA\Property(property="servicegroups", type="array", description="A list of service groups that this service is a member of.", @OA\Items(type="string", example="service_group-update-again-2")),
    *     @OA\Property(property="parallelize_check", type="boolean", description="Tells op5 Monitor whether the check should be run in parallel with other checks or serial as the only single check being executed at the time.", example=false),
    *     @OA\Property(property="check_freshness", type="boolean", description="This option is used for checking the freshness of a passive service result.", example=true),
    *     @OA\Property(property="freshness_threshold", type="integer", format="int32", description="Freshness threshold for passive checks, in seconds.", example=300),
    *     @OA\Property(property="event_handler", type="integer", description="Defines the command that should be run if event handlers are enabled and a status change just occurred.", example=1),
    *     @OA\Property(property="event_handler_args", type="string", description="Arguments to the event handler script.", example="--arg1 value1"),
    *     @OA\Property(property="low_flap_threshold", type="integer", format="int32", description="Flap detection is used to suppress notifications in case of 'unstable' hosts or services.", example=10),
    *     @OA\Property(property="high_flap_threshold", type="integer", format="int32", description="Flap detection is used to suppress notifications in case of 'flapping' hosts or services.", example=20),
    *     @OA\Property(property="flap_detection_options", type="array", @OA\Items(type="string", example="OK")),
    *     @OA\Property(property="first_notification_delay", type="integer", format="int32", description="The number of time units (usually minutes) to block notifications after a non-OK (possibly soft) state.", example=5),
    *     @OA\Property(property="contacts", type="array", description="This is a list of the contacts that should be notified whenever there are problems (or recoveries) with this object.", @OA\Items(type="string", example="admin1")),
    *     @OA\Property(property="contact_groups", type="array", description="List of references to contactgroup_name. Tells monitor which contacts should receive notifications when the service / host in question changes state.", @OA\Items(type="string", example="IT Support")),
    *     @OA\Property(property="stalking_options", type="array", @OA\Items(type="string", example="n")),
    *     @OA\Property(property="notes", type="string", description="A more descriptive information for the host or service.", example="This is a critical service."),
    *     @OA\Property(property="notes_url", type="string", description="A URL of your own choice.", example="https://example.com/service_notes"),
    *     @OA\Property(property="action_url", type="string", description="Define an optional URL that can be used to provide more actions on the host.", example="https://example.com/actions"),
    *     @OA\Property(property="icon_image", type="string", description="Defines the icon that will be used at status pages in the web GUI.", example="icon.png"),
    *     @OA\Property(property="icon_image_alt", type="string", description="Defines the alt text that appears when you put the cursor over the icon.", example="Service Icon"),
     *          ),
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Service updated successfully",
     *          @OA\JsonContent(
     *              @OA\Property(property="message", type="string", example="Service updated successfully"),
     *              @OA\Property(property="service", type="object"),
     *          ),
     *      ),
     *      @OA\Response(
     *          response=400,
     *          description="Bad request",
     *          @OA\JsonContent(
     *              @OA\Property(property="message", type="string", example="Validation error"),
     *              @OA\Property(property="errors", type="object"),
     *          ),
     *      ),
     *      @OA\Response(
     *          response=404,
     *          description="Service not found",
     *          @OA\JsonContent(
     *              @OA\Property(property="message", type="string", example="Service not found"),
     *          ),
     *      ),
     * )
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function patchService(Request $request, $params): JsonResponse
    {
        $register = $request->has('register') ? $request->input('register') : true;

        // Merge the modified request with the default value
        $request->merge(['register' => $register]);


        $params = explode(';', $params);
        $service = urldecode($params[1]);
        $resource    = urldecode($params[0]);

        // Validate the incoming request data
        $validator = Validator::make($request->all(), Service::$rules);



        if($request->parent_type)
        {
            $hostGroup = HostGroup::where('id', $resource)
            ->orWhere('hostgroup_name', $resource)
            ->first();
        }
        else
        {
            $host = Host::where('id', $resource)
            ->orWhere('host_name', $resource)
            ->where('register',1)
            ->first();
        }
        // if(!$hostGroup)
        // {
        //     return response()->json(['message' => 'Host Group not found'], 404);
        // }

        // Find the service by ID or name
        $getServicesQuery = Service::where('service_description', $service);

        if (isset($hostGroup)) {
            $getServicesQuery->Where('hostgroup_name', $hostGroup->id);
        }

        if (isset($host)) {
            $getServicesQuery->Where('host_name', $host->id);
        }

        /******************************************************* */

            if( $request->notification_period)
            {
                $notificationPeriod = \DB::table('timeperiod')->where('timeperiod_name', $request->notification_period)->pluck('id')->first();
                if (!$notificationPeriod) {
                    return response()->json(['message' => 'notification_period  not found'], 404);
                }
            }

            if($request->has('contact_groups'))
            {
                $contactGroups = \DB::table('contactgroup')->whereIn('contactgroup_name', $request->contact_groups)->pluck('id');
            }

            if($request->has('servicegroups'))
            {
                $serviceGroups = \DB::table('servicegroup')->whereIn('servicegroup_name', $request->servicegroups)->pluck('id');
            }
            if($request->has('hostgroup_name'))
            {
                $hostGroupName = \DB::table('hostgroup')->select('id')->where('hostgroup_name', $request->hostgroup_name)->first();
            }


            if($request->has('contacts'))
            {
                $contacts = \DB::table('contact')->whereIn('contact_name', $request->contacts)->pluck('id');
            }
            if($request->has('check_command'))
            {
                $checkCommand = \DB::table('command')->where('command_name', $request->check_command)->pluck('id')->first();
            }
            if( $request->has('check_period'))
            {
                $checkPeriod = \DB::table('timeperiod')->where('timeperiod_name', $request->check_period)->pluck('id')->first();
                if (!$checkPeriod) {
                    return response()->json(['message' => 'check_period  not found'], 404);
                }
            }
            $template = Service::where('name', $request->template)->where('register',0)->pluck('id')->first();
            if (!$template && !$request->register) {
                return response()->json(['message' => 'Template not found'], 404);
            }
            if($host && $request->service_description)
            {
                $ifServiceExist = Service::where('host_name',$host->id)->where('service_description', $request->service_description)->first();
                if($ifServiceExist && $request->register)
                {
                    return response()->json(['message' => 'duplicate Service'], 409);
                }
            }


        /******************************************************* */
        $services = $getServicesQuery->get();
        if($services->count() > 0)
        {
            foreach($services as $service)
            {
                // Check if the service exists
                if (!$service) {
                    return response()->json(['message' => 'Service not found'], 404);
                }
                // unset($request['hostgroup']);

                // check if the service is already there for same host
                $query = Service::where('service_description', $request->service_description);

                if (isset($hostGroup)) {
                    $query->where('hostgroup_name', $hostGroup->id);
                }

                if (isset($host)) {
                    $query->Where('host_name', $host->id);
                }

                $ifServiceExist = $query->first();

                if($ifServiceExist && $request->register && isset($host))
                {
                    return response()->json(['message' => 'duplicate Service'], 409);
                }

                // Get the field_id from the file_tbl table based on the provided host identifier
                if($request->file_id)
                {
                    $fileId = \DB::table('file_tbl')->select('id')->where('file_name', $request->file_id)->first();
                    if ($fileId) {
                        $service->file_id = $fileId->id;
                        unset($request['file_id']);
                    }
                }
                $command = Command::where('id', $request->check_command)->orWhere('command_name',"=", $request->check_command)->first();
                unset($request['check_command']);
                // Update the service attributes
                if($request->register && isset($host))
                {
                    $service->host_name = $host->id;
                }
                if($command)
                {
                    $service->check_command = isset($command->id) ? $command->id : null;
                    $service->save();
                }

                // Update the service attributes
                $service->service_description = isset($request->service_description) ? $request->service_description : $service->service_description;
                $service->max_check_attempts = $request->has('max_check_attempts') ? $request->max_check_attempts : $service->max_check_attempts;
                $service->notification_interval = $request->has('notification_interval') ? $request->notification_interval : $service->notification_interval;
                $service->notification_options = $request->has('notification_options') ? $request->notification_options : $service->notification_options;
                $service->notification_period = $request->has('notification_period') ? $notificationPeriod : $service->notification_period;
                $service->check_period = $request->has('check_period') ? $checkPeriod : $service->check_period;
                // Handle nullable fields (assuming they exist in your model)
                $service->template = ($request->has('template') && $request->template !="" && $request->template =null) ? $template : $service->template;
                $service->action_url = isset($validatedData['action_url']) ? $validatedData['action_url'] : $service->action_url;
                $service->active_checks_enabled = isset($validatedData['active_checks_enabled']) ? $validatedData['active_checks_enabled'] : $service->active_checks_enabled;
                $service->check_command = $request->has('check_command') ? $checkCommand : $service->check_command;

                $service->check_command_args = isset($validatedData['check_command_args']) ? $validatedData['check_command_args'] : $service->check_command_args;
                $service->check_freshness = isset($validatedData['check_freshness']) ? $validatedData['check_freshness'] : $service->check_freshness;
                $service->check_interval = isset($validatedData['check_interval']) ? $validatedData['check_interval'] : $service->check_interval;
                // $service->children = isset($validatedData['children']) ? $validatedData['children'] : null;
                $service->display_name = isset($validatedData['display_name']) ? $validatedData['display_name'] : $service->display_name;
                $service->event_handler = isset($validatedData['event_handler']) ? $validatedData['event_handler'] : $service->event_handler;
                $service->event_handler_args = isset($validatedData['event_handler_args']) ? $validatedData['event_handler_args'] : $service->event_handler_args;
                $service->event_handler_enabled = isset($validatedData['event_handler_enabled']) ? $validatedData['event_handler_enabled'] : $service->event_handler_enabled;
                $service->first_notification_delay = isset($validatedData['first_notification_delay']) ? $validatedData['first_notification_delay'] : $service->first_notification_delay;
                $service->flap_detection_enabled = isset($validatedData['flap_detection_enabled']) ? $validatedData['flap_detection_enabled'] : $service->flap_detection_enabled;
                $service->flap_detection_options = isset($validatedData['flap_detection_options']) ? $validatedData['flap_detection_options'] : $service->flap_detection_options;
                $service->freshness_threshold = isset($validatedData['freshness_threshold']) ? $validatedData['freshness_threshold'] : $service->freshness_threshold;
                $service->high_flap_threshold = isset($validatedData['high_flap_threshold']) ? $validatedData['high_flap_threshold'] : $service->high_flap_threshold;
                $service->icon_image = isset($validatedData['icon_image']) ? $validatedData['icon_image'] : $service->icon_image;
                $service->icon_image_alt = isset($validatedData['icon_image_alt']) ? $validatedData['icon_image_alt'] : $service->icon_image_alt;
                $service->low_flap_threshold = isset($validatedData['low_flap_threshold']) ? $validatedData['low_flap_threshold'] : $service->low_flap_threshold;
                $service->notes = isset($validatedData['notes']) ? $validatedData['notes'] : $service->notes;
                $service->notes_url = isset($validatedData['notes_url']) ? $validatedData['notes_url'] : $service->notes_url;
                $service->notifications_enabled = isset($validatedData['notifications_enabled']) ? $validatedData['notifications_enabled'] : $service->notifications_enabled;
                $service->obsess = isset($validatedData['obsess']) ? $validatedData['obsess'] : $service->obsess;
                $service->register = isset($validatedData['register']) ? $validatedData['register'] : $service->register;
                $service->passive_checks_enabled = isset($validatedData['passive_checks_enabled']) ? $validatedData['passive_checks_enabled'] : $service->passive_checks_enabled;
                $service->process_perf_data = isset($validatedData['process_perf_data']) ? $validatedData['process_perf_data'] : $service->process_perf_data;
                $service->retain_nonstatus_information = isset($validatedData['retain_nonstatus_information']) ? $validatedData['retain_nonstatus_information'] : $service->retain_nonstatus_information;
                $service->retain_status_information = isset($validatedData['retain_status_information']) ? $validatedData['retain_status_information'] : $service->retain_status_information;
                $service->retry_interval = isset($validatedData['retry_interval']) ? $validatedData['retry_interval'] : $service->retry_interval;
                $service->stalking_options = isset($validatedData['stalking_options']) ? $validatedData['stalking_options'] : $service->stalking_options;
                $service->name = isset($validatedData['name']) ? $validatedData['name'] : $service->name;
                $service->is_volatile = $request->has('is_volatile') ? $request->is_volatile : $service->is_volatile;
                $service->parallelize_check = isset($request->parallelize_check) ? $request->parallelize_check : $service->parallelize_check;

                if($service->save())
                {
                        /*
                            * creating host_contactgroup as contact_groups
                            List of references to contactgroup_name. Tells monitor which contacts
                            should receive notifications when the service / host in question changes state.

                        */
                        if(isset($contactGroups))
                        {
                            foreach($contactGroups as $contactGroup)
                            {
                                $newServiceContactGroup = new ServiceContactGroup();
                                $newServiceContactGroup->contactgroup = $contactGroup;
                                $newServiceContactGroup->service = $service->id;
                                $newServiceContactGroup->save();

                            }
                        }


                        /*
                            * creating servicegroup
                            A list of service groups that this service is a member of.

                        */
                        if(isset($serviceGroups))
                        {
                            foreach($serviceGroups as $serviceGroup)
                        {
                            $newServiceServiceGroup = new ServiceServiceGroup();
                            $newServiceServiceGroup->servicegroup = $serviceGroup;
                            $newServiceServiceGroup->service = $service->id;
                            $newServiceServiceGroup->save();

                        }
                        }


                        /*
                            * creating service contacts
                            This is a list of the contacts that should be notified whenever there are problems (or recoveries) with this object.
                            Useful if you want notifications to go to just a few people and don't want to configure contact groups.
                        */
                        if(isset($contacts))
                        {
                            foreach($contacts as $contact)
                        {
                            $newServiceServiceGroup = new ServiceContact();
                            $newServiceServiceGroup->contact = $contact;
                            $newServiceServiceGroup->service = $service->id;
                            $newServiceServiceGroup->save();
                        }
                        }
                }

                $this->updateCustomVariables($request, $service);
            }
            // Return a JSON response with a 200 OK status code
            return response()->json([
                'message' => 'Service updated successfully',
                'services' => $services,
            ], 200);

        }
        // Return a JSON response with a 200 OK status code
        return response()->json([
            'message' => 'Service not found',
            'services' => $services,
        ], 200);





    }


    /**
     * @OA\Delete(
     *      path="/api/config/service/{id}",
     *      operationId="deleteService",
     *      tags={"Services"},
     *      summary="Delete a service in op5 Monitor",
     *      description="Deletes an existing service.",
     *      security={{"basicAuth":{}}},
     *      @OA\Parameter(
     *          name="id",
     *          in="path",
     *          required=true,
     *          description="ID of the service to be deleted",
     *          @OA\Schema(type="string")
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Service deleted successfully",
     *          @OA\JsonContent(
     *              @OA\Property(property="message", type="string", example="Service deleted successfully"),
     *          ),
     *      ),
     *      @OA\Response(
     *          response=404,
     *          description="Service not found",
     *          @OA\JsonContent(
     *              @OA\Property(property="message", type="string", example="Service not found"),
     *          ),
     *      ),
     * )
     *
     * @param int $id
     * @return JsonResponse
     */
    public function deleteService($identifier): JsonResponse
    {
        // Find the service by ID or name
        // $service = Service::find($id);
        $service = Service::where('id', $identifier)->orWhere('service_description', $identifier)->first();;

        // Check if the service exists
        if (!$service) {
            return response()->json(['message' => 'Service not found'], 404);
        }

        // Delete the service
        $service->delete();

        // Return a JSON response with a 200 OK status code
        return response()->json([
            'message' => 'Service deleted successfully',
        ], 200);
    }

    private function updateCustomVariables(Request $request, Service $service)
    {
        // Using the Laravel Arr utility class to manipulate arrays efficiently
        $custom_variables = Arr::where($request->all(), function ($value, $key) {
            return strpos($key, '_') === 0;
        });

        // Begin a transaction
        DB::beginTransaction();

        try {
            // Loop through each custom variable
            foreach ($custom_variables as $key => $value) {
                // Update existing variable if it exists, otherwise insert new variable
                DB::table('custom_vars')->updateOrInsert(
                    [
                        'obj_type' => 'service',
                        'obj_id' => $service->id,
                        'variable' => $key,
                    ],
                    ['value' => $value]
                );
            }

            // Commit the transaction
            DB::commit();
        } catch (\Exception $e) {
            // Rollback the transaction in case of any errors
            DB::rollback();
            // Handle or log the exception
            // return an error response or re-throw the exception
            // depending on your application's requirements
        }
    }
    public function getDefaultFileId()
    {
       return \DB::table('file_tbl')->where('file_name', $this->defaultFileId)->pluck('id')->first();
    }


}
