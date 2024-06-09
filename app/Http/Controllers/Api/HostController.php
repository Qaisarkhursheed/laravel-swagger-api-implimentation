<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use App\Models\Host;
use App\Models\HostContactGroup;
use App\Models\HostHostGroup;
use App\Models\CustomVar;
use App\Models\HostContact;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Arr;
use DB;





class HostController extends Controller
{

    // This is the default values for the Host
    protected $defaultTemplate = 'generic-host';
    protected $defaultFileId = '/opt/naemon/etc/naemon/conf.d/templates/hosts.cfg';
    protected $defaultStalkingOptions = ['n'];
    protected $defaultRegister = true;
    protected $defaultCheckCommand = "notify-host-by-email3333";
    protected $defaultMaxCheckAttempts = 5;
    protected $defaultCheckInterval = 5;
    protected $defaultRetryInterval = 1;
    protected $defaultActiveChecksEnabled = true;
    protected $defaultPassiveChecksEnabled = true;
    protected $defaultCheckPeriod = '24x7';
    protected $defaultEventHandlerEnabled = true;
    protected $defaultFlapDetectionEnabled = true;
    protected $defaultProcessPerfData = true;
    protected $defaultRetainStatusInformation = true;
    protected $defaultRetainNonStatusInformation = true;
    protected $defaultNotificationInterval = 0;
    protected $defaultNotificationPeriod = '24x7';
    protected $defaultNotificationOptions = ['d', 'f', 'r', 's', 'u'];
    protected $defaultNotificationsEnabled = true;
    protected $defaultFlapDetectionOptions = [];

     /**
     * @OA\Get(
     *      path="/api/config/host/{host}",
     *      operationId="getAllHosts",
     *      tags={"Hosts"},
     *      summary="Get all hosts in Emca Monitor",
     *      description="Retrieves a list of all hosts or a specific host by ID or name.",
     *      security={{"basicAuth":{}}},
     *      @OA\Parameter(
     *          name="host",
     *          in="path",
     *          description="ID or name of the host (optional)",
     *          @OA\Schema(
     *              type="string",
     *          ),
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="List of hosts",
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
    public function getAllHosts(Request $request,  $host = null): JsonResponse
    {

        // Get the identifier from the request

        $identifier = urldecode($host);
        if($identifier===',' || $identifier=== "" )
            $identifier = null;
        // Query the Host model with eager loading of hostGroup
        $hosts = Host::query();

        // If an identifier is provided, filter by ID or name
        if ($identifier) {
            $hosts->where('id', $identifier)
                ->orWhere('host_name', $identifier);
        }

        // Retrieve hosts
        $hosts = $hosts->get();

        // Check if hosts are found
        if ($hosts->isEmpty()) {
            return response()->json(['error' => 'Host not found'], 404);
        }
        if(!$identifier)
        {
            $responseData = $hosts->map(function ($host) {
                return [
                    'name' => $host->host_name,
                    'resource' => URL::to('/api/config/host/' . $host->host_name)
                ];
            });
            return response()->json($responseData, 200);
        }


        $host = $hosts->first();
        $host->host_group = $host->hostGroup()->pluck('hostgroup_name')->toArray();
        $host->host_contact = $host->host_contact()->pluck('contact_name')->toArray();

        return response()->json($host, 200);
    }

   /**
     * @OA\Post(
     *      path="/api/config/host",
     *      operationId="createHost",
     *      tags={"Hosts"},
     *      summary="Create a new host in Emca Monitor",
     *      description="Creates a new host with the provided parameters.",
     *      security={{"basicAuth":{}}},
     *      @OA\RequestBody(
     *          required=true,
     *          description="Host details",
    *          @OA\JsonContent(
    *              required={"file_id", "host_name", "max_check_attempts", "notification_interval", "notification_options", "notification_period", "template"},
        *          @OA\Property(property="file_id", type="string", example="/opt/naemon/etc/naemon/conf.d/templates/hosts.cfg"),
        *          @OA\Property(property="host_name", type="string", example="example-host"),
        *          @OA\Property(property="max_check_attempts", type="integer", example=5),
        *          @OA\Property(property="notification_interval", type="integer", example=10),
        *          @OA\Property(property="notification_options", type="array", @OA\Items(type="string", example="d")),
        *          @OA\Property(property="notification_period", type="string", example="24x7"),
        *          @OA\Property(property="template", type="string", example="default-host-template"),
        *          @OA\Property(property="2d_coords", type="string", example="50,50"),
        *          @OA\Property(property="action_url", type="string", example="http://example.com"),
        *          @OA\Property(property="active_checks_enabled", type="boolean", example=true),
        *          @OA\Property(property="address", type="string", example="192.168.1.1"),
        *          @OA\Property(property="alias", type="string", example="Sample Alias"),
        *          @OA\Property(property="check_command", type="string", example="notify-host-by-email"),
        *          @OA\Property(property="check_command_args", type="string", example="-w 5 -c 10"),
        *          @OA\Property(property="check_freshness", type="boolean", example=true),
        *          @OA\Property(property="check_interval", type="integer", example=15),
        *          @OA\Property(property="check_period", type="string", example="24x7"),
        *          @OA\Property(property="contact_groups", type="array", @OA\Items(type="string", example="group-1")),
        *          @OA\Property(property="contacts", type="array", @OA\Items(type="string", example="john_doe")),
        *          @OA\Property(property="display_name", type="string", example="Display Name"),
        *          @OA\Property(property="event_handler", type="string", example=1),
        *          @OA\Property(property="event_handler_args", type="string", example="--arg1 --arg2"),
        *          @OA\Property(property="event_handler_enabled", type="boolean", example=true),
        *          @OA\Property(property="first_notification_delay", type="integer", example=5),
        *          @OA\Property(property="flap_detection_enabled", type="boolean", example=true),
        *          @OA\Property(property="flap_detection_options", type="array", @OA\Items(type="string", example="o")),
        *          @OA\Property(property="freshness_threshold", type="integer", example=60),
        *          @OA\Property(property="high_flap_threshold", type="integer", example=30),
        *          @OA\Property(property="hostgroups", type="array", @OA\Items(type="string", example="linux-servers")),
        *          @OA\Property(property="icon_image", type="string", example="icon.png"),
        *          @OA\Property(property="icon_image_alt", type="string", example="Icon Alt Text"),
        *          @OA\Property(property="low_flap_threshold", type="integer", example=10),
        *          @OA\Property(property="notes", type="string", example="Additional notes for the host"),
        *          @OA\Property(property="notes_url", type="string", example="http://example.com/notes"),
        *          @OA\Property(property="notifications_enabled", type="boolean", example=true),
        *          @OA\Property(property="obsess", type="boolean", example=false),
        *          @OA\Property(property="passive_checks_enabled", type="boolean", example=true),
        *          @OA\Property(property="process_perf_data", type="boolean", example=true),
        *          @OA\Property(property="register", type="boolean", example=true),
        *          @OA\Property(property="retain_nonstatus_information", type="boolean", example=true),
        *          @OA\Property(property="retain_status_information", type="boolean", example=true),
        *          @OA\Property(property="retry_interval", type="integer", example=5),
        *          @OA\Property(property="stalking_options", type="array", @OA\Items(type="string", example="n")),
        *          @OA\Property(property="statusmap_image", type="string", example="statusmap.png"),
        *          ),
        *      ),
        *      @OA\Response(
        *          response=201,
        *          description="Host created successfully",
        *          @OA\JsonContent(
        *              @OA\Property(property="message", type="string", example="Host created successfully"),
        *              @OA\Property(property="host", type="object"),
        *          ),
        *      ),
        *      @OA\Response(
        *          response=400,
        *          description="Bad request",
        *          @OA\JsonContent(
        *              @OA\Property(property="message", type="string", example="Validation error"),
        *              @OA\Property(property="errors", type="object", example={"host_name": {"The host_name field is required."}}),
        *          ),
        *      ),
        * )
        *
        * @param Request $request
        * @return JsonResponse
     */
    public function createHost(Request $request): JsonResponse
    {
            $register = $request->has('register') ? $request->input('register') : $this->defaultRegister;

            // Merge the modified request with the default value
            $request->merge(['register' => $register]);
            // Validate the incoming request data
            $validatedData = $request->validate([
                'file_id' => 'nullable|string',
                'host_name' => 'required|string|unique:host', // Assuming 'host' is the table name
                'max_check_attempts' => 'nullable|integer',
                'notification_interval' => 'nullable|integer',
                'notification_options' => 'nullable|array',
                'host_notification_options.*' => 'in:d,u,r,f',
                'notification_period' => 'nullable|string',
                'template' => 'nullable | string',// nullable
                '2d_coords' => 'nullable|string',
                'action_url' => 'nullable|string',
                'active_checks_enabled' => 'nullable|boolean',
                'address' => 'nullable|string',
                'alias' => 'required|string',
                //in database its type is integer so thats why
                'check_command' => 'nullable|string', //Reference pointer to command_name. This tells Monitor which command should be run to determine status of the host or service.
                'check_command_args' => 'nullable|string',
                'check_freshness' => 'nullable|boolean',
                'check_interval' => 'nullable|integer',
                'check_period' => 'nullable|string',
                // 'children' => 'nullable|array',
                'contact_groups' => 'nullable|array',
                'contacts' => 'nullable|array',
                'display_name' => 'nullable|string',
                'event_handler' => 'nullable|integer',
                'event_handler_args' => 'nullable|string',
                'event_handler_enabled' => 'boolean',
                'first_notification_delay' => 'nullable|integer',
                'flap_detection_enabled' => 'boolean',
                'flap_detection_options' => 'nullable|array', //[]
                'freshness_threshold' => 'nullable|integer',
                'high_flap_threshold' => 'nullable|integer',
                'hostgroups' => 'nullable|array',
                'icon_image' => 'nullable|string',
                'icon_image_alt' => 'nullable|string',
                'low_flap_threshold' => 'nullable|integer',
                'notes' => 'nullable|string',
                'notes_url' => 'nullable|string',
                'notifications_enabled' => 'nullable|boolean',
                'obsess' => 'nullable|boolean',
                // 'parents' => 'nullable|array',
                'passive_checks_enabled' => 'nullable|boolean',
                'process_perf_data' => 'nullable|boolean',
                'register' => 'nullable|boolean',
                'retain_nonstatus_information' => 'nullable|boolean',
                'retain_status_information' => 'nullable|boolean',
                'retry_interval' => 'nullable|integer',
                'stalking_options' => 'array|nullable',
                'stalking_options.*' => 'in:n',
                'statusmap_image' => 'nullable|string',
                "name" => "nullable| string"

            ]);

            // using the laravel Arr utility class to manipulate arrays efficiently
            $custom_variables = Arr::where($request->all(), function ($value, $key) {
                return strpos($key, '_') === 0;
            });


            if(isset($request->template) && $request->template !="" && $request->template =null)
            {
                $template = Host::where('name',$request->template )->where('register',0)->pluck('id')->first();
            }
            // // Create a new host using the Host model
            if( $request->has('file_id'))
            {
                $fileId = \DB::table('file_tbl')->where('file_name', $request->file_id)->pluck('id')->first();
            }
            if( $request->notification_period)
            {
                $notificationPeriod = \DB::table('timeperiod')->where('timeperiod_name', $request->notification_period)->pluck('id')->first();
            }

            if($request->has('contact_groups'))
            {
                $contactGroups = \DB::table('contactgroup')->whereIn('contactgroup_name', $request->contact_groups)->pluck('id');
            }

            if($request->hostgroups)
            {
                $hostGroups = \DB::table('hostgroup')->whereIn('hostgroup_name', $request->hostgroups)->pluck('id');
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


            $newHost = new Host;
            $newHost->file_id = isset($fileId) ? $fileId : $this->getDefaultFileId();
            $newHost->host_name = $validatedData['host_name'];
            $newHost->max_check_attempts = isset($validatedData['max_check_attempts']) ? $validatedData['max_check_attempts'] : $this->defaultMaxCheckAttempts;
            $newHost->notification_interval = isset($validatedData['notification_interval']) ? $validatedData['notification_interval'] : $this->defaultNotificationInterval;
            $newHost->notification_options = isset($validatedData['notification_options']) ? $validatedData['notification_options'] : $this->defaultNotificationOptions;
            $newHost->notification_period = isset($notificationPeriod) ? $notificationPeriod : $this->getNotificationPeriod();
            $newHost->check_period = isset($checkPeriod) ? $checkPeriod : $this->getDefaultCheckPeriod();
            // Handle nullable fields (assuming they exist in your model)
            $newHost->template = isset($validatedData['template']) ? $validatedData['template'] : $this->getDefaultTemplate();
            $newHost->{'2d_coords'} = isset($validatedData['2d_coords']) ? $validatedData['2d_coords'] : null;
            $newHost->action_url = isset($validatedData['action_url']) ? $validatedData['action_url'] : null;
            $newHost->active_checks_enabled = isset($validatedData['active_checks_enabled']) ? $validatedData['active_checks_enabled'] :$this->defaultActiveChecksEnabled;
            $newHost->address = isset($validatedData['address']) ? $validatedData['address'] : $validatedData['host_name'];
            $newHost->alias = isset($validatedData['alias']) ? $validatedData['alias'] : null;
            $newHost->check_command = isset($checkCommand) ? $checkCommand : $this->getDefaultCheckCommand();
            $newHost->check_command_args = isset($validatedData['check_command_args']) ? $validatedData['check_command_args'] : null;
            $newHost->check_freshness = isset($validatedData['check_freshness']) ? $validatedData['check_freshness'] : null;
            $newHost->check_interval = isset($validatedData['check_interval']) ? $validatedData['check_interval'] : $this->defaultCheckInterval;
            // $newHost->children = isset($validatedData['children']) ? $validatedData['children'] : null;
            $newHost->display_name = isset($validatedData['display_name']) ? $validatedData['display_name'] : null;
            $newHost->event_handler = isset($validatedData['event_handler']) ? $validatedData['event_handler'] : null;
            $newHost->event_handler_args = isset($validatedData['event_handler_args']) ? $validatedData['event_handler_args'] : null;
            $newHost->event_handler_enabled = isset($validatedData['event_handler_enabled']) ? $validatedData['event_handler_enabled'] : $this->defaultEventHandlerEnabled;
            $newHost->first_notification_delay = isset($validatedData['first_notification_delay']) ? $validatedData['first_notification_delay'] : null;
            $newHost->flap_detection_enabled = isset($validatedData['flap_detection_enabled']) ? $validatedData['flap_detection_enabled'] : $this->defaultFlapDetectionEnabled;
            $newHost->flap_detection_options = isset($validatedData['flap_detection_options']) ? $validatedData['flap_detection_options'] : $this->defaultFlapDetectionOptions;
            $newHost->freshness_threshold = isset($validatedData['freshness_threshold']) ? $validatedData['freshness_threshold'] : null;
            $newHost->high_flap_threshold = isset($validatedData['high_flap_threshold']) ? $validatedData['high_flap_threshold'] : null;
            $newHost->icon_image = isset($validatedData['icon_image']) ? $validatedData['icon_image'] : null;
            $newHost->icon_image_alt = isset($validatedData['icon_image_alt']) ? $validatedData['icon_image_alt'] : null;
            $newHost->low_flap_threshold = isset($validatedData['low_flap_threshold']) ? $validatedData['low_flap_threshold'] : null;
            $newHost->notes = isset($validatedData['notes']) ? $validatedData['notes'] : null;
            $newHost->notes_url = isset($validatedData['notes_url']) ? $validatedData['notes_url'] : null;
            $newHost->notifications_enabled = isset($validatedData['notifications_enabled'])? $validatedData['notifications_enabled'] : $this->defaultNotificationsEnabled;
            $newHost->obsess = isset($validatedData['obsess']) ? $validatedData['obsess'] : null;
            $newHost->register = $validatedData['register'];
            $newHost->passive_checks_enabled = isset($validatedData['passive_checks_enabled']) ? $validatedData['passive_checks_enabled'] : $this->defaultPassiveChecksEnabled;
            $newHost->process_perf_data = isset($validatedData['process_perf_data']) ? $validatedData['process_perf_data'] : $this->defaultProcessPerfData;
            $newHost->retain_nonstatus_information = isset($validatedData['retain_nonstatus_information']) ? $validatedData['retain_nonstatus_information'] : $this->defaultRetainNonStatusInformation;
            $newHost->retain_status_information = isset($validatedData['retain_status_information']) ? $validatedData['retain_status_information'] : $this->defaultRetainStatusInformation;
            $newHost->retry_interval = isset($validatedData['retry_interval']) ? $validatedData['retry_interval'] : $this->defaultRetryInterval;
            $newHost->stalking_options = isset($validatedData['stalking_options']) ? $validatedData['stalking_options'] : $this->defaultStalkingOptions;
            $newHost->statusmap_image = isset($validatedData['statusmap_image']) ? $validatedData['statusmap_image'] : null;
            $newHost->name = isset($validatedData['name']) ? $validatedData['name'] : null;



            if($request->register)
            {
                $newHost->template = isset($template->id) ?  $template->id : null;
                $newHost->save();
            }
            // Save the new contact object
            if($newHost->save())
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
                        $newHostContactGroup = new HostContactGroup();
                        $newHostContactGroup->contactgroup = $contactGroup;
                        $newHostContactGroup->host = $newHost->id;
                        $newHostContactGroup->save();

                    }
                }
                /*
                    * creating host hostgroups
                    Tells op5 Monitor which hostgroup a host belongs to. A host can be a member of several hostgroups, or none at all.
                    Hostgroups is mainly used for visual presentation and reporting.
                */
                if(isset($hostGroups))
                {
                    foreach($hostGroups as $hostGroup)
                    {
                        $newHostHostGroup = new HostHostGroup();
                        $newHostHostGroup->hostgroup = $hostGroup;
                        $newHostHostGroup->host = $newHost->id;
                        $newHostHostGroup->save();

                    }
                }

                /*
                    * creating host contacts
                    This is a list of the contacts that should be notified whenever there are problems (or recoveries) with this object.
                    Useful if you want notifications to go to just a few people and don't want to configure contact groups.
                */
                if(isset($contacts))
                {
                    foreach($contacts as $contact)
                    {
                        $newHostHostGroup = new HostContact();
                        $newHostHostGroup->contact = $contact;
                        $newHostHostGroup->host = $newHost->id;
                        $newHostHostGroup->save();
                    }
                }

                // Convert the array to a collection
                $custom_variables_collection = collect($custom_variables);

                // If there are custom variables, insert them into the database
                if ($custom_variables_collection->isNotEmpty()) {
                    // Prepare the data for bulk insert
                    $data = $custom_variables_collection->map(function ($value, $key) use ($newHost) {
                        return [
                            'obj_type' => 'host',
                            'obj_id' => $newHost->id,
                            'variable' => $key,
                            'value' => $value,
                        ];
                    })->toArray();

                    // Bulk insert the data
                    DB::table('custom_vars')->insert($data);
                }


                // Return a JSON response with a 201 Created status code
                return response()->json([
                    'message' => 'Host created successfully',
                    'host' => $newHost,
                ], 201);
            }
        return response()->json([
            'message' => 'error while creating contact',
            'status' => false,
        ], 500);
    }


    /**
     * @OA\Put(
     *      path="/api/config/host/{host}",
     *      operationId="updateHost",
     *      tags={"Hosts"},
     *      summary="Update a host in Emca Monitor",
     *      description="Updates an existing host with the provided parameters.",
     *      security={{"basicAuth":{}}},
     *      @OA\Parameter(
     *          name="host",
     *          in="path",
     *          required=true,
     *          description="ID or Name of the host to be updated",
     *          @OA\Schema(type="string")
     *      ),
     *      @OA\RequestBody(
     *          required=true,
     *          description="Host details",
     *          @OA\JsonContent(
     *              required={"file_id", "host_name", "max_check_attempts", "notification_interval", "notification_options", "notification_period", "template"},
        *          @OA\Property(property="file_id", type="string", example="/opt/naemon/etc/naemon/conf.d/templates/hosts.cfg"),
        *          @OA\Property(property="host_name", type="string", example="example-host"),
        *          @OA\Property(property="max_check_attempts", type="integer", example=5),
        *          @OA\Property(property="notification_interval", type="integer", example=10),
        *           @OA\Property(property="notification_options", type="array", @OA\Items(type="string", example="d")),
        *          @OA\Property(property="notification_period", type="string", example="24x7"),
        *          @OA\Property(property="2d_coords", type="string", example="50,50"),
        *          @OA\Property(property="action_url", type="string", example="http://example.com"),
        *          @OA\Property(property="active_checks_enabled", type="boolean", example=true),
        *          @OA\Property(property="address", type="string", example="192.168.1.1"),
        *          @OA\Property(property="alias", type="string", example="Sample Alias"),
        *          @OA\Property(property="check_command", type="string", example="notify-host-by-email"),
        *          @OA\Property(property="check_command_args", type="string", example="-w 5 -c 10"),
        *          @OA\Property(property="check_freshness", type="boolean", example=true),
        *          @OA\Property(property="check_interval", type="integer", example=15),
        *          @OA\Property(property="check_period", type="string", example="24x7"),
        *          @OA\Property(property="contact_groups", type="array", @OA\Items(type="string", example="group-1")),
        *          @OA\Property(property="contacts", type="array", @OA\Items(type="string", example="john_doe")),
        *          @OA\Property(property="display_name", type="string", example="Display Name"),
        *          @OA\Property(property="event_handler", type="string", example=1),
        *          @OA\Property(property="event_handler_args", type="string", example="--arg1 --arg2"),
        *          @OA\Property(property="event_handler_enabled", type="boolean", example=true),
        *          @OA\Property(property="first_notification_delay", type="integer", example=5),
        *          @OA\Property(property="flap_detection_enabled", type="boolean", example=true),
        *          @OA\Property(property="flap_detection_options", type="array", @OA\Items(type="string", example="o")),
        *          @OA\Property(property="freshness_threshold", type="integer", example=60),
        *          @OA\Property(property="high_flap_threshold", type="integer", example=30),
        *          @OA\Property(property="hostgroups", type="array", @OA\Items(type="string", example="linux-servers")),
        *          @OA\Property(property="icon_image", type="string", example="icon.png"),
        *          @OA\Property(property="icon_image_alt", type="string", example="Icon Alt Text"),
        *          @OA\Property(property="low_flap_threshold", type="integer", example=10),
        *          @OA\Property(property="notes", type="string", example="Additional notes for the host"),
        *          @OA\Property(property="notes_url", type="string", example="http://example.com/notes"),
        *          @OA\Property(property="notifications_enabled", type="boolean", example=true),
        *          @OA\Property(property="obsess", type="boolean", example=false),
        *          @OA\Property(property="passive_checks_enabled", type="boolean", example=true),
        *          @OA\Property(property="process_perf_data", type="boolean", example=true),
        *          @OA\Property(property="register", type="boolean", example=true),
        *          @OA\Property(property="retain_nonstatus_information", type="boolean", example=true),
        *          @OA\Property(property="retain_status_information", type="boolean", example=true),
        *          @OA\Property(property="retry_interval", type="integer", example=5),
        *          @OA\Property(property="stalking_options", type="array", @OA\Items(type="string", example="n")),
        *          @OA\Property(property="statusmap_image", type="string", example="statusmap.png"),
     *          ),
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Host updated successfully",
     *          @OA\JsonContent(
     *              @OA\Property(property="message", type="string", example="Host updated successfully"),
     *              @OA\Property(property="host", type="object"),
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
     *          description="Host not found",
     *          @OA\JsonContent(
     *              @OA\Property(property="message", type="string", example="Host not found"),
     *          ),
     *      ),
     * )
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function updateHost(Request $request, $hostName): JsonResponse
    {
        $register = $request->has('register') ? $request->input('register') : true;
        // Merge the modified request with the default value
        $request->merge(['register' => $register]);

        // Validate the incoming request data
        $validatedData = $request->validate([
            'file_id' => 'sometimes|string',
            'host_name' => 'nullable|string|unique:host', // Assuming 'host' is the table name
            'max_check_attempts' => 'sometimes|integer',
            'notification_interval' => 'sometimes|integer',
            'notification_options' => 'sometimes|array',
            'host_notification_options.*' => 'in:d,u,r,f',
            'notification_period' => 'sometimes|string',
            'template' => 'nullable | string',// sometimes
            '2d_coords' => 'nullable|string',
            'action_url' => 'nullable|string',
            'active_checks_enabled' => 'sometimes|boolean',
            'address' => 'nullable|string',
            'alias' => 'sometimes|string',
            //in database its type is integer so thats why
            'check_command' => 'sometimes|string', //Reference pointer to command_name. This tells Monitor which command should be run to determine status of the host or service.
            'check_command_args' => 'nullable|string',
            'check_freshness' => 'nullable|boolean',
            'check_interval' => 'nullable|integer',
            'check_period' => 'nullable|string',
            // 'children' => 'nullable|array',
            'contact_groups' => 'nullable|array',
            'contacts' => 'nullable|array',
            'display_name' => 'nullable|string',
            'event_handler' => 'nullable|integer',
            'event_handler_args' => 'nullable|string',
            'event_handler_enabled' => 'boolean',
            'first_notification_delay' => 'nullable|integer',
            'flap_detection_enabled' => 'boolean',
            'flap_detection_options' => 'nullable|array', //[]
            'freshness_threshold' => 'nullable|integer',
            'high_flap_threshold' => 'nullable|integer',
            'hostgroups' => 'nullable|array',
            'icon_image' => 'nullable|string',
            'icon_image_alt' => 'nullable|string',
            'low_flap_threshold' => 'nullable|integer',
            'notes' => 'nullable|string',
            'notes_url' => 'nullable|string',
            'notifications_enabled' => 'sometimes|boolean',
            'obsess' => 'nullable|boolean',
            // 'parents' => 'nullable|array',
            'passive_checks_enabled' => 'sometimes|boolean',
            'process_perf_data' => 'sometimes|boolean',
            'register' => 'sometimes|boolean',
            'retain_nonstatus_information' => 'sometimes|boolean',
            'retain_status_information' => 'sometimes|boolean',
            'retry_interval' => 'nullable|integer',
            'stalking_options' => 'array|nullable',
            'stalking_options.*' => 'in:n',
            'statusmap_image' => 'nullable|string',
            "name" => "nullable| string"
        ]);
        $updateHost = Host::where('id', $hostName)
            ->orWhere('host_name', $hostName)
            ->first();
        // Check if the host exists
        if (!$updateHost) {
            return response()->json(['message' => 'Host not found'], 404);
        }

        if(isset($request->template) && $request->template !="" && $request->template =null)
        {
            $template = Host::where('name',$request->template )->where('register',0)->pluck('id')->first();
            if (!$template) {
                return response()->json(['message' => 'Template not found'], 404);
            }
        }
        // // Create a new host using the Host model
        if( $request->has('file_id'))
        {
            $fileId = \DB::table('file_tbl')->where('file_name', $request->file_id)->pluck('id')->first();
            if (!$fileId) {
                return response()->json(['message' => 'file id not found'], 404);
            }
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

        if($request->has('hostgroups'))
        {
            $hostGroups = \DB::table('hostgroup')->whereIn('hostgroup_name', $request->hostgroups)->pluck('id');
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

        $updateHost->host_name = $request->has('host_name') ? $request->host_name : $updateHost->host_name;
        $updateHost->file_id = $request->has('file_id') ? $fileId : $updateHost->file_id;
        $updateHost->max_check_attempts = $request->has('max_check_attempts') ? $request->max_check_attempts : $updateHost->max_check_attempts;
        $updateHost->notification_interval = $request->has('notification_interval') ? $request->notification_interval : $updateHost->notification_interval;
        $updateHost->notification_options = $request->has('notification_options') ? $request->notification_options : $updateHost->notification_options;
        $updateHost->notification_period = $request->has('notification_period') ? $notificationPeriod : $updateHost->notification_period;
        $updateHost->check_period = $request->has('check_period') ? $checkPeriod : $updateHost->check_period;
        // Handle nullable fields (assuming they exist in your model)
        $updateHost->template = ($request->has('template') && $request->template !="" && $request->template =null) ? $template : $updateHost->template;
        $updateHost->{'2d_coords'} = isset($validatedData['2d_coords']) ? $validatedData['2d_coords'] : $updateHost->{'2d_coords'};
        $updateHost->action_url = isset($validatedData['action_url']) ? $validatedData['action_url'] : $updateHost->action_url;
        $updateHost->active_checks_enabled = isset($validatedData['active_checks_enabled']) ? $validatedData['active_checks_enabled'] : $updateHost->active_checks_enabled;
        $updateHost->address = isset($validatedData['address']) ? $validatedData['address'] : $updateHost->address;
        $updateHost->alias = isset($validatedData['alias']) ? $validatedData['alias'] : $updateHost->alias;
        $updateHost->check_command = $request->has('check_command') ? $checkCommand : $updateHost->check_command;

        $updateHost->check_command_args = isset($validatedData['check_command_args']) ? $validatedData['check_command_args'] : $updateHost->check_command_args;
        $updateHost->check_freshness = isset($validatedData['check_freshness']) ? $validatedData['check_freshness'] : $updateHost->check_freshness;
        $updateHost->check_interval = isset($validatedData['check_interval']) ? $validatedData['check_interval'] : $updateHost->check_interval;
        // $updateHost->children = isset($validatedData['children']) ? $validatedData['children'] : null;
        $updateHost->display_name = isset($validatedData['display_name']) ? $validatedData['display_name'] : $updateHost->display_name;
        $updateHost->event_handler = isset($validatedData['event_handler']) ? $validatedData['event_handler'] : $updateHost->event_handler;
        $updateHost->event_handler_args = isset($validatedData['event_handler_args']) ? $validatedData['event_handler_args'] : $updateHost->event_handler_args;
        $updateHost->event_handler_enabled = isset($validatedData['event_handler_enabled']) ? $validatedData['event_handler_enabled'] : $updateHost->event_handler_enabled;
        $updateHost->first_notification_delay = isset($validatedData['first_notification_delay']) ? $validatedData['first_notification_delay'] : $updateHost->first_notification_delay;
        $updateHost->flap_detection_enabled = isset($validatedData['flap_detection_enabled']) ? $validatedData['flap_detection_enabled'] : $updateHost->flap_detection_enabled;
        $updateHost->flap_detection_options = isset($validatedData['flap_detection_options']) ? $validatedData['flap_detection_options'] : $updateHost->flap_detection_options;
        $updateHost->freshness_threshold = isset($validatedData['freshness_threshold']) ? $validatedData['freshness_threshold'] : $updateHost->freshness_threshold;
        $updateHost->high_flap_threshold = isset($validatedData['high_flap_threshold']) ? $validatedData['high_flap_threshold'] : $updateHost->high_flap_threshold;
        $updateHost->icon_image = isset($validatedData['icon_image']) ? $validatedData['icon_image'] : $updateHost->icon_image;
        $updateHost->icon_image_alt = isset($validatedData['icon_image_alt']) ? $validatedData['icon_image_alt'] : $updateHost->icon_image_alt;
        $updateHost->low_flap_threshold = isset($validatedData['low_flap_threshold']) ? $validatedData['low_flap_threshold'] : $updateHost->low_flap_threshold;
        $updateHost->notes = isset($validatedData['notes']) ? $validatedData['notes'] : $updateHost->notes;
        $updateHost->notes_url = isset($validatedData['notes_url']) ? $validatedData['notes_url'] : $updateHost->notes_url;
        $updateHost->notifications_enabled = isset($validatedData['notifications_enabled']) ? $validatedData['notifications_enabled'] : $updateHost->notifications_enabled;
        $updateHost->obsess = isset($validatedData['obsess']) ? $validatedData['obsess'] : $updateHost->obsess;
        $updateHost->register = isset($validatedData['register']) ? $validatedData['register'] : $updateHost->register;
        $updateHost->passive_checks_enabled = isset($validatedData['passive_checks_enabled']) ? $validatedData['passive_checks_enabled'] : $updateHost->passive_checks_enabled;
        $updateHost->process_perf_data = isset($validatedData['process_perf_data']) ? $validatedData['process_perf_data'] : $updateHost->process_perf_data;
        $updateHost->retain_nonstatus_information = isset($validatedData['retain_nonstatus_information']) ? $validatedData['retain_nonstatus_information'] : $updateHost->retain_nonstatus_information;
        $updateHost->retain_status_information = isset($validatedData['retain_status_information']) ? $validatedData['retain_status_information'] : $updateHost->retain_status_information;
        $updateHost->retry_interval = isset($validatedData['retry_interval']) ? $validatedData['retry_interval'] : $updateHost->retry_interval;
        $updateHost->stalking_options = isset($validatedData['stalking_options']) ? $validatedData['stalking_options'] : $updateHost->stalking_options;
        $updateHost->statusmap_image = isset($validatedData['statusmap_image']) ? $validatedData['statusmap_image'] : $updateHost->statusmap_image;
        $updateHost->name = isset($validatedData['name']) ? $validatedData['name'] : $updateHost->name;


        if($updateHost->save())
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
                        $updateHostContactGroup = new HostContactGroup();
                        $updateHostContactGroup->contactgroup = $contactGroup;
                        $updateHostContactGroup->host = $updateHost->id;
                        $updateHostContactGroup->save();

                    }
                }
                /*
                    * creating host hostgroups
                    Tells op5 Monitor which hostgroup a host belongs to. A host can be a member of several hostgroups, or none at all.
                    Hostgroups is mainly used for visual presentation and reporting.
                */
                if(isset($hostGroups))
                {
                    foreach($hostGroups as $hostGroup)
                    {
                        $updateHostHostGroup = new HostHostGroup();
                        $updateHostHostGroup->hostgroup = $hostGroup;
                        $updateHostHostGroup->host = $updateHost->id;
                        $updateHostHostGroup->save();

                    }
                }

                /*
                    * creating host contacts
                    This is a list of the contacts that should be notified whenever there are problems (or recoveries) with this object.
                    Useful if you want notifications to go to just a few people and don't want to configure contact groups.
                */
                if(isset($contacts))
                {
                    foreach($contacts as $contact)
                    {
                        $updateHostHostGroup = new HostContact();
                        $updateHostHostGroup->contact = $contact;
                        $updateHostHostGroup->host = $updateHost->id;
                        $updateHostHostGroup->save();
                    }
                }
                // Update or add custom variables
                $this->updateCustomVariables($request, $updateHost);


                // Return a JSON response with a 201 Created status code
                return response()->json([
                    'message' => 'Host updated successfully',
                    'host' => $updateHost,
                ], 200);
            }
        return response()->json([
            'message' => 'error while updating contact',
            'status' => false,
        ], 500);
    }
    /**
     * @OA\Patch(
     *      path="/api/config/host/{hostName}",
     *      operationId="patchHost",
     *      tags={"Hosts"},
     *      summary="Update a host in Emca Monitor",
     *      description="Updates an existing host with the provided parameters.",
     *      security={{"basicAuth":{}}},
     *      @OA\Parameter(
     *          name="hostName",
     *          in="path",
     *          required=true,
     *          description="ID or Name of the host to be updated",
     *          @OA\Schema(type="string")
     *      ),
     *      @OA\RequestBody(
     *          required=true,
     *          description="Host details",
     *          @OA\JsonContent(
     *              required={"file_id", "host_name", "max_check_attempts", "notification_interval", "notification_options", "notification_period", "template"},
     *              @OA\Property(property="file_id", type="string", example="/opt/naemon/etc/naemon/conf.d/templates/hosts.cfg"),
     *              @OA\Property(property="host_name", type="string", example="example-host"),
     *              @OA\Property(property="max_check_attempts", type="integer", example=5),
     *              @OA\Property(property="notification_interval", type="integer", example=10),
     *              @OA\Property(property="notification_options", type="array", @OA\Items(type="string", example="d")),
     *              @OA\Property(property="notification_period", type="string", example="24x7"),
     *              @OA\Property(property="2d_coords", type="string", example="50,50"),
     *              @OA\Property(property="action_url", type="string", example="http://example.com"),
     *              @OA\Property(property="active_checks_enabled", type="boolean", example=true),
     *              @OA\Property(property="address", type="string", example="192.168.1.1"),
     *              @OA\Property(property="alias", type="string", example="Sample Alias"),
     *              @OA\Property(property="check_command", type="string", example="notify-host-by-email"),
     *              @OA\Property(property="check_command_args", type="string", example="-w 5 -c 10"),
     *              @OA\Property(property="check_freshness", type="boolean", example=true),
     *              @OA\Property(property="check_interval", type="integer", example=15),
     *              @OA\Property(property="check_period", type="string", example="24x7"),
     *              @OA\Property(property="contact_groups", type="array", @OA\Items(type="string", example="group-1")),
     *              @OA\Property(property="contacts", type="array", @OA\Items(type="string", example="john_doe")),
     *              @OA\Property(property="display_name", type="string", example="Display Name"),
     *              @OA\Property(property="event_handler", type="string", example=1),
     *              @OA\Property(property="event_handler_args", type="string", example="--arg1 --arg2"),
     *              @OA\Property(property="event_handler_enabled", type="boolean", example=true),
     *              @OA\Property(property="first_notification_delay", type="integer", example=5),
     *              @OA\Property(property="flap_detection_enabled", type="boolean", example=true),
     *              @OA\Property(property="flap_detection_options", type="array", @OA\Items(type="string", example="o")),
     *              @OA\Property(property="freshness_threshold", type="integer", example=60),
     *              @OA\Property(property="high_flap_threshold", type="integer", example=30),
     *              @OA\Property(property="hostgroups", type="array", @OA\Items(type="string", example="linux-servers")),
     *              @OA\Property(property="icon_image", type="string", example="icon.png"),
     *              @OA\Property(property="icon_image_alt", type="string", example="Icon Alt Text"),
     *              @OA\Property(property="low_flap_threshold", type="integer", example=10),
     *              @OA\Property(property="notes", type="string", example="Additional notes for the host"),
     *              @OA\Property(property="notes_url", type="string", example="http://example.com/notes"),
     *              @OA\Property(property="notifications_enabled", type="boolean", example=true),
     *              @OA\Property(property="obsess", type="boolean", example=false),
     *              @OA\Property(property="passive_checks_enabled", type="boolean", example=true),
     *              @OA\Property(property="process_perf_data", type="boolean", example=true),
     *              @OA\Property(property="register", type="boolean", example=true),
     *              @OA\Property(property="retain_nonstatus_information", type="boolean", example=true),
     *              @OA\Property(property="retain_status_information", type="boolean", example=true),
     *              @OA\Property(property="retry_interval", type="integer", example=5),
     *              @OA\Property(property="stalking_options", type="array", @OA\Items(type="string", example="n")),
     *              @OA\Property(property="statusmap_image", type="string", example="statusmap.png"),
     *          ),
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Host updated successfully",
     *          @OA\JsonContent(
     *              @OA\Property(property="message", type="string", example="Host updated successfully"),
     *              @OA\Property(property="host", type="object"),
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
     *          description="Host not found",
     *          @OA\JsonContent(
     *              @OA\Property(property="message", type="string", example="Host not found"),
     *          ),
     *      ),
     * )
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */

     public function patchHost(Request $request, $hostName): JsonResponse
     {
        $register = $request->has('register') ? $request->input('register') : true;
        $updateHost = Host::where('id', $hostName)
            ->orWhere('host_name', $hostName)
            ->first();
        // Check if the host exists
        if (!$updateHost) {
            return response()->json(['message' => 'Host not found'], 404);
        }

        // Merge the modified request with the default value
        $request->merge(['register' => $register]);
        // Validate the incoming request data
        $validatedData = $request->validate([
            'file_id' => 'sometimes|string',
            'host_name' => 'nullable|string|unique:host', // Assuming 'host' is the table name
            'max_check_attempts' => 'sometimes|integer',
            'notification_interval' => 'sometimes|integer',
            'notification_options' => 'sometimes|array',
            'host_notification_options.*' => 'in:d,u,r,f',
            'notification_period' => 'sometimes|string',
            'template' => 'nullable | string',// sometimes
            '2d_coords' => 'nullable|string',
            'action_url' => 'nullable|string',
            'active_checks_enabled' => 'sometimes|boolean',
            'address' => 'nullable|string',
            'alias' => 'sometimes|string',
            //in database its type is integer so thats why
            'check_command' => 'sometimes|string', //Reference pointer to command_name. This tells Monitor which command should be run to determine status of the host or service.
            'check_command_args' => 'nullable|string',
            'check_freshness' => 'nullable|boolean',
            'check_interval' => 'nullable|integer',
            'check_period' => 'nullable|string',
            // 'children' => 'nullable|array',
            'contact_groups' => 'nullable|array',
            'contacts' => 'nullable|array',
            'display_name' => 'nullable|string',
            'event_handler' => 'nullable|integer',
            'event_handler_args' => 'nullable|string',
            'event_handler_enabled' => 'boolean',
            'first_notification_delay' => 'nullable|integer',
            'flap_detection_enabled' => 'boolean',
            'flap_detection_options' => 'nullable|array', //[]
            'freshness_threshold' => 'nullable|integer',
            'high_flap_threshold' => 'nullable|integer',
            'hostgroups' => 'nullable|array',
            'icon_image' => 'nullable|string',
            'icon_image_alt' => 'nullable|string',
            'low_flap_threshold' => 'nullable|integer',
            'notes' => 'nullable|string',
            'notes_url' => 'nullable|string',
            'notifications_enabled' => 'sometimes|boolean',
            'obsess' => 'nullable|boolean',
            // 'parents' => 'nullable|array',
            'passive_checks_enabled' => 'sometimes|boolean',
            'process_perf_data' => 'sometimes|boolean',
            'register' => 'sometimes|boolean',
            'retain_nonstatus_information' => 'sometimes|boolean',
            'retain_status_information' => 'sometimes|boolean',
            'retry_interval' => 'nullable|integer',
            'stalking_options' => 'array|nullable',
            'stalking_options.*' => 'in:n',
            'statusmap_image' => 'nullable|string',
            "name" => "nullable| string"
        ]);

        if(isset($request->template) && $request->template !="" && $request->template =null)
        {
            $template = Host::where('name',$request->template )->where('register',0)->pluck('id')->first();
            if (!$template) {
                return response()->json(['message' => 'Template not found'], 404);
            }
        }
        // // Create a new host using the Host model
        if( $request->has('file_id'))
        {
            $fileId = \DB::table('file_tbl')->where('file_name', $request->file_id)->pluck('id')->first();
            if (!$fileId) {
                return response()->json(['message' => 'file id not found'], 404);
            }
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

        if($request->has('hostgroups'))
        {
            $hostGroups = \DB::table('hostgroup')->whereIn('hostgroup_name', $request->hostgroups)->pluck('id');
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

        $updateHost->host_name = $request->has('host_name') ? $request->host_name : $updateHost->host_name;
        $updateHost->file_id = $request->has('file_id') ? $fileId : $updateHost->file_id;
        $updateHost->max_check_attempts = $request->has('max_check_attempts') ? $request->max_check_attempts : $updateHost->max_check_attempts;
        $updateHost->notification_interval = $request->has('notification_interval') ? $request->notification_interval : $updateHost->notification_interval;
        $updateHost->notification_options = $request->has('notification_options') ? $request->notification_options : $updateHost->notification_options;
        $updateHost->notification_period = $request->has('notification_period') ? $notificationPeriod : $updateHost->notification_period;
        $updateHost->check_period = $request->has('check_period') ? $checkPeriod : $updateHost->check_period;
        // Handle nullable fields (assuming they exist in your model)
        $updateHost->template = ($request->has('template') && $request->template !="" && $request->template =null) ? $template : $updateHost->template;
        $updateHost->{'2d_coords'} = isset($validatedData['2d_coords']) ? $validatedData['2d_coords'] : $updateHost->{'2d_coords'};
        $updateHost->action_url = isset($validatedData['action_url']) ? $validatedData['action_url'] : $updateHost->action_url;
        $updateHost->active_checks_enabled = isset($validatedData['active_checks_enabled']) ? $validatedData['active_checks_enabled'] : $updateHost->active_checks_enabled;
        $updateHost->address = isset($validatedData['address']) ? $validatedData['address'] : $updateHost->address;
        $updateHost->alias = isset($validatedData['alias']) ? $validatedData['alias'] : $updateHost->alias;
        $updateHost->check_command = $request->has('check_command') ? $checkCommand : $updateHost->check_command;

        $updateHost->check_command_args = isset($validatedData['check_command_args']) ? $validatedData['check_command_args'] : $updateHost->check_command_args;
        $updateHost->check_freshness = isset($validatedData['check_freshness']) ? $validatedData['check_freshness'] : $updateHost->check_freshness;
        $updateHost->check_interval = isset($validatedData['check_interval']) ? $validatedData['check_interval'] : $updateHost->check_interval;
        // $updateHost->children = isset($validatedData['children']) ? $validatedData['children'] : null;
        $updateHost->display_name = isset($validatedData['display_name']) ? $validatedData['display_name'] : $updateHost->display_name;
        $updateHost->event_handler = isset($validatedData['event_handler']) ? $validatedData['event_handler'] : $updateHost->event_handler;
        $updateHost->event_handler_args = isset($validatedData['event_handler_args']) ? $validatedData['event_handler_args'] : $updateHost->event_handler_args;
        $updateHost->event_handler_enabled = isset($validatedData['event_handler_enabled']) ? $validatedData['event_handler_enabled'] : $updateHost->event_handler_enabled;
        $updateHost->first_notification_delay = isset($validatedData['first_notification_delay']) ? $validatedData['first_notification_delay'] : $updateHost->first_notification_delay;
        $updateHost->flap_detection_enabled = isset($validatedData['flap_detection_enabled']) ? $validatedData['flap_detection_enabled'] : $updateHost->flap_detection_enabled;
        $updateHost->flap_detection_options = isset($validatedData['flap_detection_options']) ? $validatedData['flap_detection_options'] : $updateHost->flap_detection_options;
        $updateHost->freshness_threshold = isset($validatedData['freshness_threshold']) ? $validatedData['freshness_threshold'] : $updateHost->freshness_threshold;
        $updateHost->high_flap_threshold = isset($validatedData['high_flap_threshold']) ? $validatedData['high_flap_threshold'] : $updateHost->high_flap_threshold;
        $updateHost->icon_image = isset($validatedData['icon_image']) ? $validatedData['icon_image'] : $updateHost->icon_image;
        $updateHost->icon_image_alt = isset($validatedData['icon_image_alt']) ? $validatedData['icon_image_alt'] : $updateHost->icon_image_alt;
        $updateHost->low_flap_threshold = isset($validatedData['low_flap_threshold']) ? $validatedData['low_flap_threshold'] : $updateHost->low_flap_threshold;
        $updateHost->notes = isset($validatedData['notes']) ? $validatedData['notes'] : $updateHost->notes;
        $updateHost->notes_url = isset($validatedData['notes_url']) ? $validatedData['notes_url'] : $updateHost->notes_url;
        $updateHost->notifications_enabled = isset($validatedData['notifications_enabled']) ? $validatedData['notifications_enabled'] : $updateHost->notifications_enabled;
        $updateHost->obsess = isset($validatedData['obsess']) ? $validatedData['obsess'] : $updateHost->obsess;
        $updateHost->register = isset($validatedData['register']) ? $validatedData['register'] : $updateHost->register;
        $updateHost->passive_checks_enabled = isset($validatedData['passive_checks_enabled']) ? $validatedData['passive_checks_enabled'] : $updateHost->passive_checks_enabled;
        $updateHost->process_perf_data = isset($validatedData['process_perf_data']) ? $validatedData['process_perf_data'] : $updateHost->process_perf_data;
        $updateHost->retain_nonstatus_information = isset($validatedData['retain_nonstatus_information']) ? $validatedData['retain_nonstatus_information'] : $updateHost->retain_nonstatus_information;
        $updateHost->retain_status_information = isset($validatedData['retain_status_information']) ? $validatedData['retain_status_information'] : $updateHost->retain_status_information;
        $updateHost->retry_interval = isset($validatedData['retry_interval']) ? $validatedData['retry_interval'] : $updateHost->retry_interval;
        $updateHost->stalking_options = isset($validatedData['stalking_options']) ? $validatedData['stalking_options'] : $updateHost->stalking_options;
        $updateHost->statusmap_image = isset($validatedData['statusmap_image']) ? $validatedData['statusmap_image'] : $updateHost->statusmap_image;
        $updateHost->name = isset($validatedData['name']) ? $validatedData['name'] : $updateHost->name;


        if($updateHost->save())
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
                        $updateHostContactGroup = new HostContactGroup();
                        $updateHostContactGroup->contactgroup = $contactGroup;
                        $updateHostContactGroup->host = $updateHost->id;
                        $updateHostContactGroup->save();
                    }
                }
                /*
                    * creating host hostgroups
                    Tells op5 Monitor which hostgroup a host belongs to. A host can be a member of several hostgroups, or none at all.
                    Hostgroups is mainly used for visual presentation and reporting.
                */
                if(isset($hostGroups))
                {
                    foreach($hostGroups as $hostGroup)
                    {
                        $updateHostHostGroup = new HostHostGroup();
                        $updateHostHostGroup->hostgroup = $hostGroup;
                        $updateHostHostGroup->host = $updateHost->id;
                        $updateHostHostGroup->save();

                    }
                }

                /*
                    * creating host contacts
                    This is a list of the contacts that should be notified whenever there are problems (or recoveries) with this object.
                    Useful if you want notifications to go to just a few people and don't want to configure contact groups.
                */
                if(isset($contacts))
                {
                    foreach($contacts as $contact)
                    {
                        $updateHostHostGroup = new HostContact();
                        $updateHostHostGroup->contact = $contact;
                        $updateHostHostGroup->host = $updateHost->id;
                        $updateHostHostGroup->save();
                    }
                }
                // Update or add custom variables
                $this->updateCustomVariables($request, $updateHost);


                // Return a JSON response with a 201 Created status code
                return response()->json([
                    'message' => 'Host updated successfully',
                    'host' => $updateHost,
                ], 200);
            }
        return response()->json([
            'message' => 'error while updating contact',
            'status' => false,
        ], 500);
     }

    /**
     * @OA\Delete(
     *      path="/api/config/host/{id}",
     *      operationId="deleteHost",
     *      tags={"Hosts"},
     *      summary="Delete a host in Emca Monitor",
     *      description="Deletes an existing host.",
     *      security={{"basicAuth":{}}},
     *      @OA\Parameter(
     *          name="id",
     *          in="path",
     *          required=true,
     *          description="ID of the host to be deleted",
     *          @OA\Schema(type="string")
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Host deleted successfully",
     *          @OA\JsonContent(
     *              @OA\Property(property="message", type="string", example="Host deleted successfully"),
     *          ),
     *      ),
     *      @OA\Response(
     *          response=404,
     *          description="Host not found",
     *          @OA\JsonContent(
     *              @OA\Property(property="message", type="string", example="Host not found"),
     *          ),
     *      ),
     * )
     *
     * @param int $id
     * @return JsonResponse
     */
    public function deleteHost($identifier): JsonResponse
    {
        // Find the host by ID or name
        $host = Host::where('id', $identifier)->orWhere('host_name', $identifier)->first();;

        // Check if the host exists
        if (!$host) {
            return response()->json(['message' => 'Host not found'], 404);
        }

        // Delete the host
        $host->delete();

        // Return a JSON response with a 200 OK status code
        return response()->json([
            'message' => 'Host deleted successfully',
        ], 200);
    }
    private function updateCustomVariables(Request $request, Host $host)
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
                        'obj_type' => 'host',
                        'obj_id' => $host->id,
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
    public function getDefaultTemplate()
    {
       return Host::where('name', $this->defaultTemplate)->where('register',0)->pluck('id')->first();
    }
    public function getDefaultFileId()
    {
       return \DB::table('file_tbl')->where('file_name', $this->defaultFileId)->pluck('id')->first();
    }
    public function getNotificationPeriod()
    {
        return  \DB::table('timeperiod')->where('timeperiod_name', $this->defaultNotificationPeriod)->pluck('id')->first();
    }
    public function getDefaultCheckPeriod()
    {
        return  \DB::table('timeperiod')->where('timeperiod_name', $this->defaultCheckPeriod)->pluck('id')->first();
    }
    public function getDefaultCheckCommand()
    {
        return \DB::table('command')->where('command_name', $this->defaultCheckCommand)->pluck('id')->first();
    }


}
