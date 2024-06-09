<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\HostGroup;
use App\Models\HostGroupMember;
use App\Models\HostHostGroup;
use App\Models\Host;
use Illuminate\Support\Facades\URL;



class HostGroupController extends Controller
{
     /**
     * @OA\Get(
     *      path="/api/config/hostgroup",
     *      operationId="getAllhostGroups",
     *      tags={"HostGroups"},
     *      summary="Get all Host Groups or single hostGroup in Emca Monitor",
     *      description="Retrieves a list of all Host Groups or a specific hostGroup by ID or name.",
     *      security={{"basicAuth":{}}},
     *      @OA\Parameter(
     *          name="identifier",
     *          in="query",
     *          description="ID or name of the hostGroup (optional)",
     *          @OA\Schema(
     *              type="string",
     *          ),
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="List of Host Groups",
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
    public function getAllHostGroups(Request $request,  $identifier = null)
    {
        // Get the identifier from the request
        $identifier = urldecode($identifier);
        if($identifier==='{identifier}' || $identifier=== "" )
            $identifier = null;


        // Retrieve the Host Groups using the HostGroup model and check for both ID and name
        $hostGroups = [];

        if ($identifier) {
            $hostGroups = HostGroup::where('id', $identifier)
                ->orWhere('hostgroup_name', $identifier)
                ->first();

            // Check if the Host Groups is found
            if (!$hostGroups) {
                return response()->json(['error' => 'Host Group not found'], 404);
            }
            // Return a JSON response
            return response()->json($hostGroups, 200);
        } else {

            $hostGroups = HostGroup::all();
            $responseData = $hostGroups->map(function ($hostgroup) {
                return [
                    'name' => $hostgroup->hostgroup_name,
                    'resource' => URL::to('/api/config/hostgroup/' . $hostgroup->hostgroup_name)
                ];
            });
            return response()->json($responseData, 200);
        }
    }

    /**
     * @OA\Post(
     *      path="/api/config/hostgroup",
     *      operationId="createHostGroups",
     *      tags={"HostGroups"},
     *      summary="Create a new HostGroup in Emca Monitor",
     *      description="Creates a new HostGroup with the provided parameters.",
     *      security={{"basicAuth":{}}},
     *      @OA\RequestBody(
     *          required=true,
     *          description="command details",
     *         @OA\JsonContent(
    *             required={"hostgroup_name", "file_id"},
    *              @OA\Property(property="hostgroup_name", type="string", example="host_group"),
    *              @OA\Property(property="file_id", type="string", example="/opt/naemon/etc/naemon/conf.d/localhost.cfg"),
    *              @OA\Property(property="action_url", type="string", example="notify-host-by-email"),
    *              @OA\Property(property="alias", type="string", example="..alias"),
    *              @OA\Property(property="hostgroup_members", type="array",  @OA\Items(type="string", example="hostGroup_member_id")),
    *              @OA\Property(property="members", type="array", @OA\Items(type="string", example="member_id")),
    *              @OA\Property(property="notes", type="string", example=""),
    *              @OA\Property(property="notes_url", type="string", example=""),
    *              @OA\Property(property="register", type="string", example=""),
    *          ),
     *      ),
     *      @OA\Response(
     *          response=201,
     *          description="Host Group created successfully",
     *          @OA\JsonContent(
     *              @OA\Property(property="message", type="string", example="Host Group created successfully"),
     *              @OA\Property(property="hostGroup", type="object"),
     *          ),
     *      ),
     *      @OA\Response(
     *          response=400,
     *          description="Bad request",
     *          @OA\JsonContent(
     *              @OA\Property(property="message", type="string", example="Validation error"),
     *              @OA\Property(property="errors", type="object", example={"Host Group description": {"The Host Group description field is required."}}),
     *          ),
     *      ),
     * )
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function createHostGroups(Request $request)
    {
        // Validate the incoming request data
        $validatedData = $request->validate([
            'hostgroup_name' => 'required|string|unique:hostgroup',
            'file_id' => 'required|string',
            'register' => 'nullable|boolean',
            'alias' => 'nullable|string',
            "action_url" => "nullable|string",
            "members" => "nullable | array",
            "members.*" => "string",
            'hostgroup_members' => 'nullable|array',
            'hostgroup_members.*' => 'string',
            "notes" => "string | nullable",
            "notes_url" => "nullable | string",

        ]);
        if( $request->file_id)
        {
            $fileId = \DB::table('file_tbl')->select('id')->where('file_name', $request->file_id)->first();
            if ($fileId) {
                $request['file_id'] = $fileId->id;
            }
            else
            {
                return response()->json(['message' => 'file id not found'], 404);
            }
        }

        $hostGroup = new HostGroup();
        $hostGroup->hostgroup_name = $request->hostgroup_name;
        $hostGroup->file_id = $request->file_id;
        $hostGroup->register = $request->register;
        $hostGroup->action_url = $request->action_url;
        $hostGroup->notes = $request->notes;
        $hostGroup->alias = $request->alias;
        $hostGroup->notes_url = $request->notes_url;
        $hostGroup->save();

        if($hostGroup)
        {
            if($request->hostgroup_members)
            {
                // Extract members from the request
                $members = $request->input('hostgroup_members');
                $members = HostGroup::whereIn('hostgroup_name',$members)->pluck('id')->toArray();

                // Prepare data for insertion
                $data = array_map(function ($member) use ($hostGroup) {
                    return ['hostgroup' => $hostGroup->id, 'members' => $member];
                }, $members);

                // Create rows in the hostgroup_members table
                HostGroupMember::insert($data);
            }
            if($request->members)
            {
                // Extract members from the request
                $members = $request->input('members');
                $members = Host::whereIn('host_name',$members)->pluck('id')->toArray();

                // Prepare data for insertion
                $data = array_map(function ($member) use ($hostGroup) {
                    return ['hostgroup' => $hostGroup->id, 'host' => $member];
                }, $members);

                // Create rows in the hostgroup_members table
                HostHostGroup::insert($data);
            }

        }

        // Return a JSON response with a 201 Created status code
        return response()->json([
            'message' => 'HostGroup created successfully',
            'host_group' => $hostGroup,
        ], 201);
    }

    /**
     * @OA\Put(
     *      path="/api/config/hostgroup/{id}",
     *      operationId="updateHostGroups",
     *      tags={"HostGroups"},
     *      summary="Update an existing HostGroup in Emca Monitor",
     *      description="Updates an existing HostGroup with the provided parameters.",
     *      security={{"basicAuth":{}}},
     *      @OA\Parameter(
     *          name="id",
     *          description="ID or name of the HostGroup to be updated",
     *          required=true,
     *          in="path",
     *          @OA\Schema(type="string")
     *      ),
     *      @OA\RequestBody(
     *          required=true,
     *          description="Updated HostGroup details",
     *          @OA\JsonContent(
     *              required={"hostgroup_name", "file_id"},
     *              @OA\Property(property="hostgroup_name", type="string", example="host_group"),
     *              @OA\Property(property="file_id", type="string", example="/opt/naemon/etc/naemon/conf.d/localhost.cfg"),
     *              @OA\Property(property="action_url", type="string", example="notify-host-by-email"),
     *              @OA\Property(property="alias", type="integer", example="alias"),
     *              @OA\Property(property="hostgroup_members", type="array",  @OA\Items(type="string", example="hostGroup_member_id")),
     *              @OA\Property(property="members", type="array", @OA\Items(type="string", example="member_id")),
     *              @OA\Property(property="notes", type="string", example=""),
     *              @OA\Property(property="notes_url", type="string", example=""),
     *              @OA\Property(property="register", type="string", example=""),
     *          ),
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Host Group updated successfully",
     *          @OA\JsonContent(
     *              @OA\Property(property="message", type="string", example="Host Group updated successfully"),
     *              @OA\Property(property="hostGroup", type="object"),
     *          ),
     *      ),
     *      @OA\Response(
     *          response=400,
     *          description="Bad request",
     *          @OA\JsonContent(
     *              @OA\Property(property="message", type="string", example="Validation error"),
     *              @OA\Property(property="errors", type="object", example={"Host Group description": {"The Host Group description field is required."}}),
     *          ),
     *      ),
     *      @OA\Response(
     *          response=404,
     *          description="Host Group not found",
     *          @OA\JsonContent(
     *              @OA\Property(property="message", type="string", example="Host Group not found"),
     *          ),
     *      ),
     * )
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function updateHostGroups(Request $request, $id)
    {
        // Validate the incoming request data
        $validatedData = $request->validate([
            'hostgroup_name' => 'required|string|unique:hostgroup',
            'file_id' => 'required|string',
            'register' => 'nullable|boolean',
            "action_url" => "nullable|string",
            "members" => "nullable | array",
            'hostgroup_members' => 'nullable|array',
            'hostgroup_members.*' => 'string',
            "notes" => "string | nullable",
            "notes_url" => "nullable | string",
        ]);

        // Find the hostgroup by ID or Name

        $hostGroup = HostGroup::where('id', $id)
                    ->orWhere('hostgroup_name', $id)
                    ->first();

        if( $request->file_id)
        {
            $fileId = \DB::table('file_tbl')->select('id')->where('file_name', $request->file_id)->first();
            if ($fileId) {
                $request['file_id'] = $fileId->id;
            }
            else
            {
                return response()->json(['message' => 'file id not found'], 404);
            }
        }
        // Check if the hostgroup exists
        if (!$hostGroup) {
            return response()->json([
                'message' => 'Host Group not found',
            ], 404);
        }

        // Update the hostgroup with the new data
        $hostGroup->hostgroup_name = $request->hostgroup_name;
        $hostGroup->file_id = $request->file_id;
        $hostGroup->register = $request->register;
        $hostGroup->action_url = $request->action_url;
        $hostGroup->notes = $request->notes;
        $hostGroup->alias = $request->alias;
        $hostGroup->notes_url = $request->notes_url;
        $hostGroup->save();

        if ($request->has('hostgroup_members') && $hostGroup) {

            if($request->hostgroup_members)
                {
                    // Extract members from the request
                    $members = $request->input('hostgroup_members');
                    $members = HostGroup::whereIn('hostgroup_name',$members)->pluck('id')->toArray();

                    // Prepare data for insertion
                    $data = array_map(function ($member) use ($hostGroup) {
                        return ['hostgroup' => $hostGroup->id, 'members' => $member];
                    }, $members);

                    // Create rows in the hostgroup_members table
                    HostGroupMember::insert($data);
                }
                if($request->members)
                {
                    // Extract members from the request
                    $members = $request->input('members');
                    $members = Host::whereIn('host_name',$members)->pluck('id')->toArray();

                    // Prepare data for insertion
                    $data = array_map(function ($member) use ($hostGroup) {
                        return ['hostgroup' => $hostGroup->id, 'host' => $member];
                    }, $members);

                    // Create rows in the hostgroup_members table
                    HostHostGroup::insert($data);
                }

        }

        // Return a JSON response with a 200 OK status code
        return response()->json([
            'message' => 'Host Group updated successfully',
            'host_group' => $hostGroup,
        ], 200);
    }
    /**
         * @OA\Patch(
         *      path="/api/config/hostgroup/{hostgroup}",
         *      operationId="updateHostGroup",
         *      tags={"HostGroups"},
         *      summary="Update an existing HostGroup in Emca Monitor",
         *      description="Updates an existing HostGroup with the provided parameters.",
         *      security={{"basicAuth":{}}},
         *      @OA\Parameter(
         *          name="hostgroup",
         *          description="ID or name of the HostGroup to be updated",
         *          required=true,
         *          in="path",
         *          @OA\Schema(type="string")
         *      ),
         *      @OA\RequestBody(
         *          required=true,
         *          description="Updated HostGroup details",
         *          @OA\JsonContent(
         *              required={"file_id"},
         *              @OA\Property(property="hostgroup_name", type="string", example="updated_host_group_name"),
         *              @OA\Property(property="file_id", type="string", example="/opt/naemon/etc/naemon/conf.d/localhost.cfg"),
         *              @OA\Property(property="action_url", type="string", example="notify-host-by-email"),
         *              @OA\Property(property="alias", type="integer", example="alias"),
         *              @OA\Property(property="hostgroup_members", type="array", @OA\Items(type="string", example="hostGroup_member_id")),
         *              @OA\Property(property="members", type="array", @OA\Items(type="string", example="member_id")),
         *              @OA\Property(property="notes", type="string", example="Updated notes for the host group"),
         *              @OA\Property(property="notes_url", type="string", example="http://example.com/updated_notes"),
         *              @OA\Property(property="register", type="boolean", example=true),
         *          ),
         *      ),
         *      @OA\Response(
         *          response=200,
         *          description="Host Group updated successfully",
         *          @OA\JsonContent(
         *              @OA\Property(property="message", type="string", example="Host Group updated successfully"),
         *              @OA\Property(property="hostGroup", type="object"),
         *          ),
         *      ),
         *      @OA\Response(
         *          response=400,
         *          description="Bad request",
         *          @OA\JsonContent(
         *              @OA\Property(property="message", type="string", example="Validation error"),
         *              @OA\Property(property="errors", type="object", example={"Host Group description": {"The Host Group description field is required."}}),
         *          ),
         *      ),
         *      @OA\Response(
         *          response=404,
         *          description="Host Group not found",
         *          @OA\JsonContent(
         *              @OA\Property(property="message", type="string", example="Host Group not found"),
         *          ),
         *      ),
         * )
         *
         * @param Request $request
         * @param int $id
         * @return JsonResponse
     */


     public function patchHostGroup(Request $request, $hostGroup)
    {
        // Validate the incoming request data
        $validatedData = $request->validate([
            'hostgroup_name' => 'sometimes|string|unique:hostgroup',
            'file_id' => 'sometimes|string',
            'register' => 'nullable|boolean',
            "action_url" => "nullable|string",
            "members" => "nullable | array",
            'hostgroup_members' => 'nullable|array',
            'hostgroup_members.*' => 'string',
            "notes" => "string | nullable",
            "notes_url" => "nullable | string",
        ]);

        // Find the hostgroup by ID or Name

        $hostGroup = HostGroup::where('id', $hostGroup)
            ->orWhere('hostgroup_name', $hostGroup)
            ->first();

        // Check if the hostgroup exists
        if (!$hostGroup) {
            return response()->json([
                'message' => 'Host Group not found',
            ], 404);
        }

        // Check if file_id is present in the request
        if ($request->has('file_id')) {
            // Attempt to find the file_id in the database
            $fileId = \DB::table('file_tbl')->select('id')->where('file_name', $request->file_id)->first();

            if ($fileId) {
                // If file_id is found, assign its value to $hostGroup->file_id
                $hostGroup->file_id = $fileId->id;
            } else {
                // If file_id is not found, return an error response
                return response()->json(['message' => 'File ID not found'], 404);
            }
        }

        // Update the hostgroup with the new data
        $hostGroup->hostgroup_name = $request->input('hostgroup_name', $hostGroup->hostgroup_name);
        $hostGroup->register = $request->input('register', $hostGroup->register);
        $hostGroup->action_url = $request->input('action_url', $hostGroup->action_url);
        $hostGroup->notes = $request->input('notes', $hostGroup->notes);
        $hostGroup->alias = $request->input('alias', $hostGroup->alias);
        $hostGroup->notes_url = $request->input('notes_url', $hostGroup->notes_url);

        // Save the updated hostgroup
        $hostGroup->save();

        if ($hostGroup) {

            if($request->has('hostgroup_members'))
            {
                // Extract members from the request
                $members = $request->input('hostgroup_members');
                $members = HostGroup::whereIn('hostgroup_name',$members)->pluck('id')->toArray();

                // Prepare data for insertion
                $data = array_map(function ($member) use ($hostGroup) {
                    return ['hostgroup' => $hostGroup->id, 'members' => $member];
                }, $members);

                // Create rows in the hostgroup_members table
                HostGroupMember::insert($data);
            }
            if($request->has('members'))
            {
                // Extract members from the request
                $members = $request->input('members');
                $members = Host::whereIn('host_name',$members)->pluck('id')->toArray();

                // Prepare data for insertion
                $data = array_map(function ($member) use ($hostGroup) {
                    return ['hostgroup' => $hostGroup->id, 'host' => $member];
                }, $members);

                // Create rows in the hostgroup_members table
                HostHostGroup::insert($data);
            }
        }

        // Return a JSON response with a 200 OK status code
        return response()->json([
            'message' => 'Host Group updated successfully',
            'host_group' => $hostGroup,
        ], 200);
    }

    /**
     * @OA\Delete(
     *      path="/api/config/hostgroup/{id}",
     *      operationId="deleteHostGroup",
     *      tags={"HostGroups"},
     *      summary="Delete a specific Host Groups in Emca Monitor",
     *      description="Deletes a Host Groups by ID or name.",
     *      security={{"basicAuth":{}}},
     *      @OA\Parameter(
     *          name="id",
     *          in="path",
     *          required=true,
     *          description="ID or name of the Host Groups to delete",
     *          @OA\Schema(
     *              type="string",
     *          ),
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Host Groups deleted successfully",
     *      ),
     *      @OA\Response(
     *          response=404,
     *          description="Host Groups not found",
     *          @OA\JsonContent(
     *              type="object",
     *              @OA\Property(property="error", type="string", example="Host Groups not found"),
     *          ),
     *      ),
     * )
     *
     * @param int|string $id
     * @return JsonResponse
     */
    public function deleteHostGroup($identifier)
    {

        // Retrieve the Host Groups using the HostGroup model
        $hostGroup = HostGroup::where('id', $identifier)->orWhere('hostgroup_name', $identifier)->first();;

        // Check if the Host Groups is found
        if (!$hostGroup) {
            return response()->json(['error' => 'Host Group not found'], 404);
        }

        // Delete the Host Groups
        $hostGroup->delete();

        // Return a JSON response
        return response()->json([
            'message' => 'HostGroup deleted successfully',
        ], 200);
    }

}
