<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use App\Models\ContactContactGroup;
use App\Models\ContactGroupMembers;
use Illuminate\Support\Facades\URL;
use App\Models\Contact;



class ContactController extends Controller
{
     /**
     * @OA\Get(
     *      path="/api/config/contact/{identifier}",
     *      operationId="getAllContacts",
     *      tags={"contacts"},
     *      summary="Get all contacts or single contact in Emca Monitor",
     *      description="Retrieves a list of all contacts or a specific contact by ID or name.",
     *      security={{"basicAuth":{}}},
     *      @OA\Parameter(
     *          name="identifier",
     *          in="path",
     *          description="ID or name of the contact (optional)",
     *          @OA\Schema(
     *              type="string",
     *              example=""
     *          ),
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="List of contacts",
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
    public function getAllContacts(Request $request, $identifier = null): JsonResponse
    {
        // Get the identifier from the request
        $identifier = urldecode($identifier);

        // Query the Contact model
        $contactsQuery = Contact::query();

        // If an identifier is provided, filter by ID or name
        if ($identifier !== null && $identifier !== '{identifier}' && $identifier !== "") {
            $contactsQuery->where('id', $identifier)
                ->orWhere('contact_name', $identifier);
        }
        else {
            $identifier = null;
        }

        // Retrieve contacts
        $contacts = $contactsQuery->get();

        // Check if contacts are found
        if ($contacts->isEmpty()) {
            return response()->json(['error' => 'Contact not found'], 404);
        }

        // Prepare the response data
        if (!$identifier) {
            // If no identifier provided, return an array of objects
            $responseData = $contacts->map(function ($contact) {
                return [
                    'name' => $contact->contact_name,
                    'resource' => URL::to('/api/config/contact/' . $contact->contact_name)
                ];
            });
        } else {
            // If identifier provided, return just the object
            $responseData = $contacts->first();
        }

        // Return a JSON response
        return response()->json($responseData, 200);
    }


    /**
     * @OA\Post(
     *      path="/api/config/contact",
     *      operationId="createContact",
     *      tags={"contacts"},
     *      summary="Create a new contact in Emca Monitor",
     *      description="Creates a new contact with the provided parameters.",
     *      security={{"basicAuth":{}}},
     *      @OA\RequestBody(
     *          required=true,
     *          description="contact details",
     *         @OA\JsonContent(
    *              required={"alias", "contact_name", "host_notification_options", "host_notification_period", "service_notification_options", "service_notification_period"},
    *              @OA\Property(property="alias", type="string", example="New test contact"),
    *              @OA\Property(property="contact_name", type="string", example="john_doe"),
    *              @OA\Property(property="file_id", type="string", example="/opt/naemon/etc/naemon/conf.d/contacts.cfg"),
    *              @OA\Property(property="host_notification_options", type="array", @OA\Items(type="integer", example="d")),
    *              @OA\Property(property="host_notification_period", type="string", example="24x7"),
    *              @OA\Property(property="service_notification_options", type="array", @OA\Items(type="integer", example="c")),
    *              @OA\Property(property="service_notification_period", type="string", example="24x7"),
    *              @OA\Property(property="address1", type="string", example="address1"),
    *              @OA\Property(property="address2", type="string", example="address2"),
    *              @OA\Property(property="address3", type="string", example="address3"),
    *              @OA\Property(property="address4", type="string", example="address4"),
    *              @OA\Property(property="address5", type="string", example="address5"),
    *              @OA\Property(property="address6", type="string", example="address6"),
    *              @OA\Property(property="can_submit_commands", type="boolean", example=true),
    *              @OA\Property(property="contactgroups", type="array", @OA\Items(type="string", example="admin")),
    *              @OA\Property(property="email", type="string", example="test@gmail.com"),
    *              @OA\Property(property="groups", type="array", @OA\Items(type="string", example="admin")),
    *              @OA\Property(property="host_notification_cmds", type="string", example="notify-host-by-email"),
    *              @OA\Property(property="host_notification_cmds_args", type="string", example="host_notification_cmds_args"),
    *              @OA\Property(property="host_notifications_enabled", type="boolean", example=true),
    *              @OA\Property(property="pager", type="string", example="pager"),
    *              @OA\Property(property="register", type="boolean", example=true),
    *              @OA\Property(property="retain_nonstatus_information", type="boolean", example=true),
    *              @OA\Property(property="retain_status_information", type="boolean", example=true),
    *              @OA\Property(property="service_notification_cmds", type="string", example="notify-service-by-email"),
    *              @OA\Property(property="service_notification_cmds_args", type="string", example="service_notification_cmds_args"),
    *              @OA\Property(property="service_notifications_enabled", type="boolean", example=true),
    *              @OA\Property(property="template", type="string", example=""),
    *          ),
     *      ),
     *      @OA\Response(
     *          response=201,
     *          description="contact created successfully",
     *          @OA\JsonContent(
     *              @OA\Property(property="message", type="string", example="contact created successfully"),
     *              @OA\Property(property="contact", type="object"),
     *          ),
     *      ),
     *      @OA\Response(
     *          response=400,
     *          description="Bad request",
     *          @OA\JsonContent(
     *              @OA\Property(property="message", type="string", example="Validation error"),
     *              @OA\Property(property="errors", type="object", example={"contact_description": {"The contact_description field is required."}}),
     *          ),
     *      ),
     * )
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function createContact(Request $request): JsonResponse
    {
        // Validate the incoming request data
        $validatedData = $request->validate([
            'alias' => 'required|string',
            'contact_name' => 'required|string|unique:contact',
            'file_id' => 'required|string',
            'host_notification_options' => 'required|array',
            'host_notification_options.*' => 'in:d,r',
            'host_notification_period' => 'required|string',
            'service_notification_options' => 'required|array',
            'service_notification_options.*' => 'in:c,w,r',
            'service_notification_period' => 'required|string',
            'can_submit_commands' => 'nullable|boolean',
            'contactgroups' => 'nullable|array',
            'contactgroups.*' => 'string',
            'email' => 'nullable|string',
            // 'enable_access' => 'nullable|boolean',
            'groups' => 'nullable|array',
            'host_notification_cmds' => 'nullable|string',
            'host_notification_cmds_args' => 'nullable|string',
            'host_notifications_enabled' => 'nullable|boolean',
            // 'modules' => 'nullable|array',
            'pager' => 'nullable|string',
            // 'password' => 'nullable|string',
            // 'realname' => 'nullable|string',
            'register' => 'nullable|boolean',
            'retain_nonstatus_information' => 'nullable|boolean',
            'retain_status_information' => 'nullable|boolean',
            'service_notification_cmds' => 'nullable|string',
            'service_notification_cmds_args' => 'nullable|string',
            'service_notifications_enabled' => 'nullable|boolean',
            'template' => 'nullable|string',
            'minimum_value'=>'nullable|int',
            "address1" => "nullable|string",
            "address2" => "nullable|string",
            "address3" => "nullable|string",
            "address4" => "nullable|string",
            "address5" => "nullable|string",
            "address6" => "nullable|string",
            "name"     => "nullable|string",
        ]);

        if( $request->file_id)
        {
            $fileId = \DB::table('file_tbl')->where('file_name', $request->file_id)->pluck('id')->first();
            if (!$fileId) {
                return response()->json(['message' => 'file id not found'], 404);
            }
        }
        if( $request->template)
        {
            $template = \DB::table('host')->where('id', $request->template)->orWhere('host_name', $request->template)->pluck('id')->first();
            if (!$fileId) {
                return response()->json(['message' => 'template id not found'], 404);
            }
        }
        if( $request->host_notification_period)
        {
            $host_notification_period = \DB::table('timeperiod')->where('timeperiod_name', $request->host_notification_period)->pluck('id')->first();
            if (!$host_notification_period) {
                return response()->json(['message' => 'host_notification_period  not found'], 404);
            }
        }
        if( $request->service_notification_period)
        {
            $service_notification_period = \DB::table('timeperiod')->where('timeperiod_name', $request->service_notification_period)->pluck('id')->first();
            if (!$service_notification_period) {
                return response()->json(['message' => 'service_notification_period  not found'], 404);
            }
        }

        if($request->contactgroups)
        {
            $contactGroups = \DB::table('contactgroup')->whereIn('contactgroup_name', $request->contactgroups)->pluck('id');
        }
        if($request->groups)
        {
            $groups = \DB::table('contactgroup')->whereIn('contactgroup_name', $request->contactgroups)->pluck('id');
        }
        if($request->host_notification_cmds)
        {
            $hostNotificationCmds = \DB::table('command')->where('command_name', $request->contactgroups)->pluck('id')->first();
        }
        if($request->service_notification_cmds)
        {
            $serviceNotificationCmds = \DB::table('command')->where('command_name', $request->contactgroups)->pluck('id')->first();
        }



        $newContact = new Contact();
        // Assign values directly from the request
        $newContact->alias = $request->alias;
        $newContact->contact_name = $request->contact_name;
        $newContact->file_id = $fileId;
        $newContact->host_notification_options = $request->host_notification_options;
        $newContact->host_notification_period = $host_notification_period;
        $newContact->service_notification_options = $request->service_notification_options;
        $newContact->service_notification_period = $service_notification_period;
        $newContact->can_submit_commands = $request->can_submit_commands;
        $newContact->email = $request->email;
        $newContact->host_notification_cmds = $hostNotificationCmds;
        $newContact->host_notification_cmds_args = $request->host_notification_cmds_args;
        $newContact->host_notifications_enabled = $request->host_notifications_enabled;
        // $newContact->modules = $request->modules;
        $newContact->pager = $request->pager;
        // $newContact->password = $request->password;
        // $newContact->realname = $request->realname;
        $newContact->register = $request->register;
        $newContact->retain_nonstatus_information = $request->retain_nonstatus_information;
        $newContact->retain_status_information = $request->retain_status_information;
        $newContact->service_notification_cmds = $serviceNotificationCmds;
        $newContact->service_notification_cmds_args = $request->service_notification_cmds_args;
        $newContact->service_notifications_enabled = $request->service_notifications_enabled;
        $newContact->template = isset($request->template) ? $template : null;
        $newContact->minimum_value = $request->minimum_value;
        $newContact->address1 = $request->address1;
        $newContact->address2 = $request->address2;
        $newContact->address3 = $request->address3;
        $newContact->address4 = $request->address4;
        $newContact->address5 = $request->address5;
        $newContact->address6 = $request->address6;
        $newContact->name = $request->name;


        // Save the new contact object
        if($newContact->save())
        {
            foreach($contactGroups as $contactGroup)
            {
                $newContactGroup = new ContactContactGroup();
                $newContactGroup->contactgroup = $contactGroup;
                $newContactGroup->contact = $newContact->id;
                $newContactGroup->save();

            }
            foreach($groups as $group)
            {
                $newContactGroupMember = new ContactGroupMembers();
                $newContactGroupMember->contactgroup = $group;
                $newContactGroupMember->members = $newContact->id;
                $newContactGroupMember->save();

            }

            return response()->json([
                'message' => 'Contact created successfully',
                'contact' => $newContact,
            ], 201);
        }
        return response()->json([
            'message' => 'error while creating contact',
            'status' => false,
        ], 500);

    }


    /**
     * @OA\Put(
     *      path="/api/config/contact/{id}",
     *      operationId="updateContact",
     *      tags={"contacts"},
     *      summary="Update a contact in Emca Monitor",
     *      description="Updates an existing contact with the provided parameters.",
     *      security={{"basicAuth":{}}},
     *      @OA\Parameter(
     *          name="id",
     *          in="path",
     *          required=true,
     *          description="ID of the contact to be updated",
     *          @OA\Schema(type="string")
     *      ),
     *      @OA\RequestBody(
     *          required=true,
     *          description="contact details",
     *          @OA\JsonContent(
     *             required={"alias", "contact_name", "file_id", "host_notification_options", "host_notification_period", "service_notification_options", "service_notification_period"},
    *              @OA\Property(property="alias", type="string", example="New test contact"),
    *              @OA\Property(property="contact_name", type="string", example="john_doe"),
    *              @OA\Property(property="file_id", type="string", example="/opt/naemon/etc/naemon/conf.d/contacts.cfg"),
    *              @OA\Property(property="host_notification_options", type="array", @OA\Items(type="integer", example="d")),
    *              @OA\Property(property="host_notification_period", type="integer", example=1),
    *              @OA\Property(property="service_notification_options", type="array", @OA\Items(type="integer", example="w")),
    *              @OA\Property(property="service_notification_period", type="integer", example=1),
    *              @OA\Property(property="address1", type="string", example="address1"),
    *              @OA\Property(property="address2", type="string", example="address2"),
    *              @OA\Property(property="address3", type="string", example="address3"),
    *              @OA\Property(property="address4", type="string", example="address4"),
    *              @OA\Property(property="address5", type="string", example="address5"),
    *              @OA\Property(property="address6", type="string", example="address6"),
    *              @OA\Property(property="can_submit_commands", type="boolean", example=true),
    *              @OA\Property(property="contactgroups", type="array", @OA\Items(type="integer", example=1)),
    *              @OA\Property(property="email", type="string", example="test@gmail.com"),
    *              @OA\Property(property="groups", type="array", @OA\Items(type="integer", example=1)),
    *              @OA\Property(property="host_notification_cmds", type="integer", example=1),
    *              @OA\Property(property="host_notification_cmds_args", type="string", example="host_notification_cmds_args"),
    *              @OA\Property(property="host_notifications_enabled", type="boolean", example=true),
    *              @OA\Property(property="pager", type="string", example="pager"),
    *              @OA\Property(property="register", type="boolean", example=true),
    *              @OA\Property(property="retain_nonstatus_information", type="boolean", example=true),
    *              @OA\Property(property="retain_status_information", type="boolean", example=true),
    *              @OA\Property(property="service_notification_cmds", type="integer", example=1),
    *              @OA\Property(property="service_notification_cmds_args", type="string", example="service_notification_cmds_args"),
    *              @OA\Property(property="service_notifications_enabled", type="boolean", example=true),
    *              @OA\Property(property="template", type="string", example=""),
     *          ),
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="contact updated successfully",
     *          @OA\JsonContent(
     *              @OA\Property(property="message", type="string", example="contact updated successfully"),
     *              @OA\Property(property="contact", type="object"),
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
     *          description="contact not found",
     *          @OA\JsonContent(
     *              @OA\Property(property="message", type="string", example="contact not found"),
     *          ),
     *      ),
     * )
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function updateContact(Request $request, $id): JsonResponse
    {
        $updateContact = Contact::where('id', $id)->orWhere('contact_name',"=", $id)->first();

        // Check if the command exists
        if (!$updateContact) {
            return response()->json(['message' => 'Contact not found'], 404);
        }

         // Validate the incoming request data
         $validatedData = $request->validate([
            'alias' => 'sometimes|string',
            'contact_name' => 'sometimes|string|unique:contact',
            'file_id' => 'sometimes|string',
            'host_notification_options' => 'sometimes|array',
            'host_notification_options.*' => 'in:d,r',
            'host_notification_period' => 'sometimes|string',
            'service_notification_options' => 'sometimes|array',
            'service_notification_options.*' => 'in:c,w,r',
            'service_notification_period' => 'sometimes|string',
            'can_submit_commands' => 'nullable|boolean',
            'contactgroups' => 'nullable|array',
            'contactgroups.*' => 'string',
            'email' => 'nullable|string',
            // 'enable_access' => 'nullable|boolean',
            'groups' => 'nullable|array',
            'host_notification_cmds' => 'nullable|string',
            'host_notification_cmds_args' => 'nullable|string',
            'host_notifications_enabled' => 'nullable|boolean',
            // 'modules' => 'nullable|array',
            'pager' => 'nullable|string',
            // 'password' => 'nullable|string',
            // 'realname' => 'nullable|string',
            'register' => 'nullable|boolean',
            'retain_nonstatus_information' => 'nullable|boolean',
            'retain_status_information' => 'nullable|boolean',
            'service_notification_cmds' => 'nullable|string',
            'service_notification_cmds_args' => 'nullable|string',
            'service_notifications_enabled' => 'nullable|boolean',
            'template' => 'nullable|string',
            'minimum_value'=>'nullable|int',
            "address1" => "nullable|string",
            "address2" => "nullable|string",
            "address3" => "nullable|string",
            "address4" => "nullable|string",
            "address5" => "nullable|string",
            "address6" => "nullable|string",
            "name"     => "nullable|string",
        ]);

        if( $request->file_id)
        {
            $fileId = \DB::table('file_tbl')->where('file_name', $request->file_id)->pluck('id')->first();
            if (!$fileId) {
                return response()->json(['message' => 'file id not found'], 404);
            }
        }
        if( $request->template)
        {
            $template = \DB::table('host')->where('id', $request->template)->orWhere('host_name', $request->template)->pluck('id')->first();
            if (!$fileId) {
                return response()->json(['message' => 'template id not found'], 404);
            }
        }
        if( $request->host_notification_period)
        {
            $host_notification_period = \DB::table('timeperiod')->where('timeperiod_name', $request->host_notification_period)->pluck('id')->first();
            if (!$host_notification_period) {
                return response()->json(['message' => 'host_notification_period  not found'], 404);
            }
        }
        if( $request->service_notification_period)
        {
            $service_notification_period = \DB::table('timeperiod')->where('timeperiod_name', $request->service_notification_period)->pluck('id')->first();
            if (!$service_notification_period) {
                return response()->json(['message' => 'service_notification_period  not found'], 404);
            }
        }

        if($request->contactgroups)
        {
            $contactGroups = \DB::table('contactgroup')->whereIn('contactgroup_name', $request->contactgroups)->pluck('id');
        }
        if($request->groups)
        {
            $groups = \DB::table('contactgroup')->whereIn('contactgroup_name', $request->contactgroups)->pluck('id');
        }
        if($request->host_notification_cmds)
        {
            $hostNotificationCmds = \DB::table('command')->where('command_name', $request->contactgroups)->pluck('id')->first();
        }
        if($request->service_notification_cmds)
        {
            $serviceNotificationCmds = \DB::table('command')->where('command_name', $request->contactgroups)->pluck('id')->first();
        }


       // Assign values directly from the request if provided
        $updateContact->alias = $request->has('alias') ? $request->alias : $updateContact->alias;
        $updateContact->contact_name = $request->has('contact_name') ? $request->contact_name : $updateContact->contact_name;
        $updateContact->file_id = $request->has('file_id') ? $fileId : $updateContact->file_id;
        $updateContact->host_notification_options = $request->has('host_notification_options') ? $request->host_notification_options : $updateContact->host_notification_options;
        $updateContact->host_notification_period = $request->has('host_notification_period') ? $host_notification_period : $updateContact->host_notification_period;
        $updateContact->service_notification_options = $request->has('service_notification_options') ? $request->service_notification_options : $updateContact->service_notification_options;
        $updateContact->service_notification_period = $request->has('service_notification_period') ? $service_notification_period : $updateContact->service_notification_period;
        $updateContact->can_submit_commands = $request->has('can_submit_commands') ? $request->can_submit_commands : $updateContact->can_submit_commands;
        $updateContact->email = $request->has('email') ? $request->email : $updateContact->email;
        $updateContact->host_notification_cmds = $request->has('host_notification_cmds') ? $hostNotificationCmds : $updateContact->host_notification_cmds;
        $updateContact->host_notification_cmds_args = $request->has('host_notification_cmds_args') ? $request->host_notification_cmds_args : $updateContact->host_notification_cmds_args;
        $updateContact->host_notifications_enabled = $request->has('host_notifications_enabled') ? $request->host_notifications_enabled : $updateContact->host_notifications_enabled;
        $updateContact->pager = $request->has('pager') ? $request->pager : $updateContact->pager;
        $updateContact->register = $request->has('register') ? $request->register : $updateContact->register;
        $updateContact->retain_nonstatus_information = $request->has('retain_nonstatus_information') ? $request->retain_nonstatus_information : $updateContact->retain_nonstatus_information;
        $updateContact->retain_status_information = $request->has('retain_status_information') ? $request->retain_status_information : $updateContact->retain_status_information;
        $updateContact->service_notification_cmds = $request->has('service_notification_cmds') ? $serviceNotificationCmds : $updateContact->service_notification_cmds;
        $updateContact->service_notification_cmds_args = $request->has('service_notification_cmds_args') ? $request->service_notification_cmds_args : $updateContact->service_notification_cmds_args;
        $updateContact->service_notifications_enabled = $request->has('service_notifications_enabled') ? $request->service_notifications_enabled : $updateContact->service_notifications_enabled;
        $updateContact->template = $request->has('template') ? $template : $updateContact->template;
        $updateContact->minimum_value = $request->has('minimum_value') ? $request->minimum_value : $updateContact->minimum_value;
        $updateContact->address1 = $request->has('address1') ? $request->address1 : $updateContact->address1;
        $updateContact->address2 = $request->has('address2') ? $request->address2 : $updateContact->address2;
        $updateContact->address3 = $request->has('address3') ? $request->address3 : $updateContact->address3;
        $updateContact->address4 = $request->has('address4') ? $request->address4 : $updateContact->address4;
        $updateContact->address5 = $request->has('address5') ? $request->address5 : $updateContact->address5;
        $updateContact->address6 = $request->has('address6') ? $request->address6 : $updateContact->address6;
        $updateContact->name = $request->has('name') ? $request->name : $updateContact->name;



        // Save the new contact object
        if($updateContact->save())
        {
            foreach($contactGroups as $contactGroup)
            {
                $updateContactGroup = new ContactContactGroup();
                $updateContactGroup->contactgroup = $contactGroup;
                $updateContactGroup->contact = $updateContact->id;
                $updateContactGroup->save();

            }
            foreach($groups as $group)
            {
                $updateContactGroupMember = new ContactGroupMembers();
                $updateContactGroupMember->contactgroup = $group;
                $updateContactGroupMember->members = $updateContact->id;
                $updateContactGroupMember->save();

            }

            return response()->json([
                'message' => 'Contact updated successfully',
                'contact' => $updateContact,
            ], 200);
        }
        return response()->json([
            'message' => 'error while creating contact',
            'status' => false,
        ], 500);

    }
    /**
     * @OA\Patch(
     *      path="/api/config/contact/{id}",
     *      operationId="patchContact",
     *      tags={"contacts"},
     *      summary="Update a contact in Emca Monitor",
     *      description="Updates an existing contact with the provided parameters.",
     *      security={{"basicAuth":{}}},
     *      @OA\Parameter(
     *          name="id",
     *          in="path",
     *          required=true,
     *          description="ID of the contact to be updated",
     *          @OA\Schema(type="string")
     *      ),
     *      @OA\RequestBody(
     *          required=true,
     *          description="contact details",
     *          @OA\JsonContent(
     *             required={"alias", "contact_name", "file_id", "host_notification_options", "host_notification_period", "service_notification_options", "service_notification_period"},
     *              @OA\Property(property="alias", type="string", example="New test contact"),
     *              @OA\Property(property="contact_name", type="string", example="john_doe"),
     *              @OA\Property(property="file_id", type="string", example="/opt/naemon/etc/naemon/conf.d/contacts.cfg"),
     *              @OA\Property(property="host_notification_options", type="array", @OA\Items(type="integer", example="d")),
     *              @OA\Property(property="host_notification_period", type="integer", example=1),
     *              @OA\Property(property="service_notification_options", type="array", @OA\Items(type="integer", example="w")),
     *              @OA\Property(property="service_notification_period", type="integer", example=1),
     *              @OA\Property(property="address1", type="string", example="address1"),
     *              @OA\Property(property="address2", type="string", example="address2"),
     *              @OA\Property(property="address3", type="string", example="address3"),
     *              @OA\Property(property="address4", type="string", example="address4"),
     *              @OA\Property(property="address5", type="string", example="address5"),
     *              @OA\Property(property="address6", type="string", example="address6"),
     *              @OA\Property(property="can_submit_commands", type="boolean", example=true),
     *              @OA\Property(property="contactgroups", type="array", @OA\Items(type="integer", example=1)),
     *              @OA\Property(property="email", type="string", example="test@gmail.com"),
     *              @OA\Property(property="groups", type="array", @OA\Items(type="integer", example=1)),
     *              @OA\Property(property="host_notification_cmds", type="integer", example=1),
     *              @OA\Property(property="host_notification_cmds_args", type="string", example="host_notification_cmds_args"),
     *              @OA\Property(property="host_notifications_enabled", type="boolean", example=true),
     *              @OA\Property(property="pager", type="string", example="pager"),
     *              @OA\Property(property="register", type="boolean", example=true),
     *              @OA\Property(property="retain_nonstatus_information", type="boolean", example=true),
     *              @OA\Property(property="retain_status_information", type="boolean", example=true),
     *              @OA\Property(property="service_notification_cmds", type="integer", example=1),
     *              @OA\Property(property="service_notification_cmds_args", type="string", example="service_notification_cmds_args"),
     *              @OA\Property(property="service_notifications_enabled", type="boolean", example=true),
     *              @OA\Property(property="template", type="string", example=""),
     *          ),
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="contact updated successfully",
     *          @OA\JsonContent(
     *              @OA\Property(property="message", type="string", example="contact updated successfully"),
     *              @OA\Property(property="contact", type="object"),
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
     *          description="contact not found",
     *          @OA\JsonContent(
     *              @OA\Property(property="message", type="string", example="contact not found"),
     *          ),
     *      ),
     * )
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */

    public function patchContact(Request $request, $id): JsonResponse
    {
        $updateContact = Contact::where('id', $id)->orWhere('contact_name',"=", $id)->first();

        // Check if the command exists
        if (!$updateContact) {
            return response()->json(['message' => 'Contact not found'], 404);
        }

         // Validate the incoming request data
         $validatedData = $request->validate([
            'alias' => 'sometimes|string',
            'contact_name' => 'sometimes|string|unique:contact',
            'file_id' => 'sometimes|string',
            'host_notification_options' => 'sometimes|array',
            'host_notification_options.*' => 'in:d,r',
            'host_notification_period' => 'sometimes|string',
            'service_notification_options' => 'sometimes|array',
            'service_notification_options.*' => 'in:c,w,r',
            'service_notification_period' => 'sometimes|string',
            'can_submit_commands' => 'nullable|boolean',
            'contactgroups' => 'nullable|array',
            'contactgroups.*' => 'string',
            'email' => 'nullable|string',
            // 'enable_access' => 'nullable|boolean',
            'groups' => 'nullable|array',
            'host_notification_cmds' => 'nullable|string',
            'host_notification_cmds_args' => 'nullable|string',
            'host_notifications_enabled' => 'nullable|boolean',
            // 'modules' => 'nullable|array',
            'pager' => 'nullable|string',
            // 'password' => 'nullable|string',
            // 'realname' => 'nullable|string',
            'register' => 'nullable|boolean',
            'retain_nonstatus_information' => 'nullable|boolean',
            'retain_status_information' => 'nullable|boolean',
            'service_notification_cmds' => 'nullable|string',
            'service_notification_cmds_args' => 'nullable|string',
            'service_notifications_enabled' => 'nullable|boolean',
            'template' => 'nullable|string',
            'minimum_value'=>'nullable|int',
            "address1" => "nullable|string",
            "address2" => "nullable|string",
            "address3" => "nullable|string",
            "address4" => "nullable|string",
            "address5" => "nullable|string",
            "address6" => "nullable|string",
            "name"     => "nullable|string",
        ]);

        if( $request->has('file_id'))
        {
            $fileId = \DB::table('file_tbl')->where('file_name', $request->file_id)->pluck('id')->first();
            if (!$fileId) {
                return response()->json(['message' => 'file id not found'], 404);
            }
        }
        if( $request->has('template'))
        {
            $template = \DB::table('host')->where('id', $request->template)->orWhere('host_name', $request->template)->pluck('id')->first();
            if (!$fileId) {
                return response()->json(['message' => 'template id not found'], 404);
            }
        }
        if( $request->has('host_notification_period'))
        {
            $host_notification_period = \DB::table('timeperiod')->where('timeperiod_name', $request->host_notification_period)->pluck('id')->first();
            if (!$host_notification_period) {
                return response()->json(['message' => 'host_notification_period  not found'], 404);
            }
        }
        if( $request->service_notification_period)
        {
            $service_notification_period = \DB::table('timeperiod')->where('timeperiod_name', $request->service_notification_period)->pluck('id')->first();
            if (!$service_notification_period) {
                return response()->json(['message' => 'service_notification_period  not found'], 404);
            }
        }

        if($request->has('contactgroups'))
        {
            $contactGroups = \DB::table('contactgroup')->whereIn('contactgroup_name', $request->contactgroups)->pluck('id');
        }
        if($request->has('groups'))
        {
            $groups = \DB::table('contactgroup')->whereIn('contactgroup_name', $request->contactgroups)->pluck('id');
        }
        if($request->has('host_notification_cmds'))
        {
            $hostNotificationCmds = \DB::table('command')->where('command_name', $request->contactgroups)->pluck('id')->first();
        }
        if($request->has('service_notification_cmds'))
        {
            $serviceNotificationCmds = \DB::table('command')->where('command_name', $request->contactgroups)->pluck('id')->first();
        }


       // Assign values directly from the request if provided
        $updateContact->alias = $request->has('alias') ? $request->alias : $updateContact->alias;
        $updateContact->contact_name = $request->has('contact_name') ? $request->contact_name : $updateContact->contact_name;
        $updateContact->file_id = $request->has('file_id') ? $fileId : $updateContact->file_id;
        $updateContact->host_notification_options = $request->has('host_notification_options') ? $request->host_notification_options : $updateContact->host_notification_options;
        $updateContact->host_notification_period = $request->has('host_notification_period') ? $host_notification_period : $updateContact->host_notification_period;
        $updateContact->service_notification_options = $request->has('service_notification_options') ? $request->service_notification_options : $updateContact->service_notification_options;
        $updateContact->service_notification_period = $request->has('service_notification_period') ? $service_notification_period : $updateContact->service_notification_period;
        $updateContact->can_submit_commands = $request->has('can_submit_commands') ? $request->can_submit_commands : $updateContact->can_submit_commands;
        $updateContact->email = $request->has('email') ? $request->email : $updateContact->email;
        $updateContact->host_notification_cmds = $request->has('host_notification_cmds') ? $hostNotificationCmds : $updateContact->host_notification_cmds;
        $updateContact->host_notification_cmds_args = $request->has('host_notification_cmds_args') ? $request->host_notification_cmds_args : $updateContact->host_notification_cmds_args;
        $updateContact->host_notifications_enabled = $request->has('host_notifications_enabled') ? $request->host_notifications_enabled : $updateContact->host_notifications_enabled;
        $updateContact->pager = $request->has('pager') ? $request->pager : $updateContact->pager;
        $updateContact->register = $request->has('register') ? $request->register : $updateContact->register;
        $updateContact->retain_nonstatus_information = $request->has('retain_nonstatus_information') ? $request->retain_nonstatus_information : $updateContact->retain_nonstatus_information;
        $updateContact->retain_status_information = $request->has('retain_status_information') ? $request->retain_status_information : $updateContact->retain_status_information;
        $updateContact->service_notification_cmds = $request->has('service_notification_cmds') ? $serviceNotificationCmds : $updateContact->service_notification_cmds;
        $updateContact->service_notification_cmds_args = $request->has('service_notification_cmds_args') ? $request->service_notification_cmds_args : $updateContact->service_notification_cmds_args;
        $updateContact->service_notifications_enabled = $request->has('service_notifications_enabled') ? $request->service_notifications_enabled : $updateContact->service_notifications_enabled;
        $updateContact->template = $request->has('template') ? $template : $updateContact->template;
        $updateContact->minimum_value = $request->has('minimum_value') ? $request->minimum_value : $updateContact->minimum_value;
        $updateContact->address1 = $request->has('address1') ? $request->address1 : $updateContact->address1;
        $updateContact->address2 = $request->has('address2') ? $request->address2 : $updateContact->address2;
        $updateContact->address3 = $request->has('address3') ? $request->address3 : $updateContact->address3;
        $updateContact->address4 = $request->has('address4') ? $request->address4 : $updateContact->address4;
        $updateContact->address5 = $request->has('address5') ? $request->address5 : $updateContact->address5;
        $updateContact->address6 = $request->has('address6') ? $request->address6 : $updateContact->address6;
        $updateContact->name = $request->has('name') ? $request->name : $updateContact->name;



        // Save the new contact object
        if($updateContact->save())
        {
            if(isset($contactGroups))
            {
                foreach($contactGroups as $contactGroup)
                {
                    $updateContactGroup = new ContactContactGroup();
                    $updateContactGroup->contactgroup = $contactGroup;
                    $updateContactGroup->contact = $updateContact->id;
                    $updateContactGroup->save();

                }
            }
            if(isset($groups))
            {
                foreach($groups as $group)
                {
                    $updateContactGroupMember = new ContactGroupMembers();
                    $updateContactGroupMember->contactgroup = $group;
                    $updateContactGroupMember->members = $updateContact->id;
                    $updateContactGroupMember->save();

                }
            }

            return response()->json([
                'message' => 'Contact updated successfully',
                'contact' => $updateContact,
            ], 200);
        }
        return response()->json([
            'message' => 'error while creating contact',
            'status' => false,
        ], 500);
    }



    /**
     * @OA\Delete(
     *      path="/api/config/contact/{id}",
     *      operationId="deleteContact",
     *      tags={"contacts"},
     *      summary="Delete a Contact in op5 Monitor",
     *      description="Deletes an existing Contact.",
     *      security={{"basicAuth":{}}},
     *      @OA\Parameter(
     *          name="id",
     *          in="path",
     *          required=true,
     *          description="ID of the Contact to be deleted",
     *          @OA\Schema(type="string")
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Contact deleted successfully",
     *          @OA\JsonContent(
     *              @OA\Property(property="message", type="string", example="Contact deleted successfully"),
     *          ),
     *      ),
     *      @OA\Response(
     *          response=404,
     *          description="Contact not found",
     *          @OA\JsonContent(
     *              @OA\Property(property="message", type="string", example="Contact not found"),
     *          ),
     *      ),
     * )
     *
     * @param int $id
     * @return JsonResponse
     */
    public function deleteContact($identifier): JsonResponse
    {
        // Find the Contact by ID or name

        $contact = Contact::where('id', $identifier)->orWhere('contact_name', $identifier)->first();;

        // Check if the contact exists
        if (!$contact) {
            return response()->json(['message' => 'Contact not found'], 404);
        }

        // Delete the Contact
        $contact->delete();

        // Return a JSON response with a 200 OK status code
        return response()->json([
            'message' => 'Contact deleted successfully',
        ], 200);
    }

}
