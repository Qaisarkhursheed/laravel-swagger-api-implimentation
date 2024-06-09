<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ServiceGroup;
use App\Models\ServiceGroupMember;
use App\Models\ServiceServiceGroup;
use App\Models\Service;
use Illuminate\Support\Facades\URL;

class ServiceGroupController extends Controller
{
    /**
     * @OA\Get(
     *      path="/api/config/servicegroup",
     *      operationId="getAllservicegroup",
     *      tags={"ServiceGroups"},
     *      summary="Get all Service Group or single service in Emca Monitor",
     *      description="Retrieves a list of all Service Group or a specific service by ID or name.",
     *      security={{"basicAuth":{}}},
     *      @OA\Parameter(
     *          name="identifier",
     *          in="query",
     *          description="ID or name of the service (optional)",
     *          @OA\Schema(
     *              type="string",
     *          ),
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="List of Service Group",
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
    public function getAllServiceGroups(Request $request,  $identifier = null)
    {
        // Get the identifier from the request
        $identifier = urldecode($identifier);
        if($identifier==='{identifier}' || $identifier=== "" )
            $identifier = null;

        // Retrieve the service group using the ServiceGroup model and check for both ID and name
        $serviceGroups = [];

        if ($identifier) {
            $serviceGroups = ServiceGroup::where('id', $identifier)
                ->orWhere('servicegroup_name', $identifier)
                ->first();

            // Check if the service group is found
            if (!$serviceGroups) {
                return response()->json(['error' => 'Service group not found'], 404);
            }
            return response()->json($serviceGroups, 200);
        } else {
            $serviceGroups = ServiceGroup::all();
            $responseData = $serviceGroups->map(function ($servicegroup) {
                return [
                    'name' => $servicegroup->servicegroup_name,
                    'resource' => URL::to('/api/config/servicegroup/' . $servicegroup->servicegroup_name)
                ];
            });
            return response()->json($responseData, 200);
        }
    }
    /**
     * @OA\Post(
     *      path="/api/config/servicegroup",
     *      operationId="createServiceGroups",
     *      tags={"ServiceGroups"},
     *      summary="Create a new ServiceGroup in Emca Monitor",
     *      description="Creates a new ServiceGroup with the provided parameters.",
     *      security={{"basicAuth":{}}},
     *      @OA\RequestBody(
     *          required=true,
     *          description="ServiceGroup details",
     *         @OA\JsonContent(
     *              required={"servicegroup_name", "file_id"},
     *              @OA\Property(property="servicegroup_name", type="string", example="service_group"),
     *              @OA\Property(property="file_id", type="string", example="/opt/naemon/etc/naemon/conf.d/localhost.cfg"),
     *              @OA\Property(property="action_url", type="string", example="notify-service-by-email"),
     *              @OA\Property(property="alias", type="integer", example="alias"),
     *              @OA\Property(property="servicegroup_members", type="array",  @OA\Items(type="string", example="serviceGroup_member_id")),
     *              @OA\Property(property="members", type="array", @OA\Items(type="string", example="member_id")),
     *              @OA\Property(property="notes", type="string", example=""),
     *              @OA\Property(property="notes_url", type="string", example=""),
     *              @OA\Property(property="register", type="string", example=""),
     *          ),
     *      ),
     *      @OA\Response(
     *          response=201,
     *          description="Service Group created successfully",
     *          @OA\JsonContent(
     *              @OA\Property(property="message", type="string", example="Service Group created successfully"),
     *              @OA\Property(property="service_group", type="object"),
     *          ),
     *      ),
     *      @OA\Response(
     *          response=400,
     *          description="Bad request",
     *          @OA\JsonContent(
     *              @OA\Property(property="message", type="string", example="Validation error"),
     *              @OA\Property(property="errors", type="object", example={"Service Group description": {"The Service Group description field is required."}}),
     *          ),
     *      ),
     * )
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function createServiceGroups(Request $request)
    {
        // Validate the incoming request data
        $validatedData = $request->validate([
            'servicegroup_name' => 'required|string|unique:servicegroup',
            'file_id' => 'required|string',
            'register' => 'nullable|boolean',
            "action_url" => "nullable|string",
            "members" => "nullable | array",
            'servicegroup_members' => 'nullable|array',
            'servicegroup_members.*' => 'string',
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

        // Create a new servicegroup using the servicegroup model
        $serviceGroup = new ServiceGroup();
        $serviceGroup->servicegroup_name = $request->servicegroup_name;
        $serviceGroup->alias = $request->alias;
        $serviceGroup->notes = $request->notes;
        $serviceGroup->notes_url = $request->notes_url;
        $serviceGroup->action_url = $request->action_url;
        $serviceGroup->register = $request->register;
        $serviceGroup->file_id = $request->file_id;
        $serviceGroup->save();

        if($serviceGroup)
        {
            if($request->servicegroup_members )
            {
                // Extract members from the request
                $members = $request->input('servicegroup_members');
                $members = ServiceGroup::whereIn('servicegroup_name',$members)->pluck('id')->toArray();

                // Prepare data for insertion
                $data = array_map(function ($member) use ($serviceGroup) {
                    return ['servicegroup' => $serviceGroup->id, 'members' => $member];
                }, $members);

                // Create rows in the servicegroup_members table
                ServiceGroupMember::insert($data);
            }
            if($request->members )
            {
                // Extract members from the request
                $members = $request->input('members');
                $members = Service::whereIn('service_description',$members)->pluck('id')->toArray();

                // Prepare data for insertion
                $data = array_map(function ($member) use ($serviceGroup) {
                    return ['servicegroup' => $serviceGroup->id, 'service' => $member];
                }, $members);

                // Create rows in the servicegroup_members table
                ServiceServiceGroup::insert($data);
            }

        }


        // Return a JSON response with a 201 Created status code
        return response()->json([
            'message' => 'Service Group created successfully',
            'service_group' => $serviceGroup,
        ], 201);
    }

    /**
     * @OA\Put(
     *      path="/api/config/servicegroup/{id}",
     *      operationId="updateServiceGroups",
     *      tags={"ServiceGroups"},
     *      summary="Update an existing ServiceGroup in Emca Monitor",
     *      description="Updates an existing ServiceGroup with the provided parameters.",
     *      security={{"basicAuth":{}}},
     *      @OA\Parameter(
     *          name="id",
     *          description="ID or name of the ServiceGroup to be updated",
     *          required=true,
     *          in="path",
     *          @OA\Schema(type="string")
     *      ),
     *      @OA\RequestBody(
     *          required=true,
     *          description="Updated ServiceGroup details",
     *          @OA\JsonContent(
     *              required={"servicegroup_name", "file_id"},
     *              @OA\Property(property="servicegroup_name", type="string", example="service_group"),
     *              @OA\Property(property="file_id", type="string", example="/opt/naemon/etc/naemon/conf.d/localhost.cfg"),
     *              @OA\Property(property="action_url", type="string", example="notify-service-by-email"),
     *              @OA\Property(property="alias", type="integer", example="alias"),
     *              @OA\Property(property="servicegroup_members", type="array",  @OA\Items(type="string", example="serviceGroup_member_id")),
     *              @OA\Property(property="members", type="array", @OA\Items(type="string", example="member_id")),
     *              @OA\Property(property="notes", type="string", example=""),
     *              @OA\Property(property="notes_url", type="string", example=""),
     *              @OA\Property(property="register", type="string", example=""),
     *          ),
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Service Group updated successfully",
     *          @OA\JsonContent(
     *              @OA\Property(property="message", type="string", example="Service Group updated successfully"),
     *              @OA\Property(property="service_group", type="object"),
     *          ),
     *      ),
     *      @OA\Response(
     *          response=400,
     *          description="Bad request",
     *          @OA\JsonContent(
     *              @OA\Property(property="message", type="string", example="Validation error"),
     *              @OA\Property(property="errors", type="object", example={"Service Group description": {"The Service Group description field is required."}}),
     *          ),
     *      ),
     *      @OA\Response(
     *          response=404,
     *          description="Service Group not found",
     *          @OA\JsonContent(
     *              @OA\Property(property="message", type="string", example="Service Group not found"),
     *          ),
     *      ),
     * )
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function updateServiceGroups(Request $request, $id)
    {
        // Validate the incoming request data
        $validatedData = $request->validate([
            'servicegroup_name' => 'required|string|unique:servicegroup',
            'file_id' => 'required|string',
            'register' => 'nullable|boolean',
            'servicegroup_members' => 'nullable|array',
            'servicegroup_members.*' => 'string',
        ]);

        // Find the servicegroup by ID or Name
        $serviceGroup = ServiceGroup::where('id', $id)
                ->orWhere('servicegroup_name', $id)
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
        // Check if the servicegroup exists
        if (!$serviceGroup) {
            return response()->json([
                'message' => 'Service Group not found',
            ], 404);
        }
        // Update the servicegroup with the new data
        $serviceGroup->servicegroup_name = $request->servicegroup_name;
        $serviceGroup->alias = $request->alias;
        $serviceGroup->notes = $request->notes;
        $serviceGroup->notes_url = $request->notes_url;
        $serviceGroup->action_url = $request->action_url;
        $serviceGroup->register = $request->register;
        $serviceGroup->file_id = $request->file_id;
        $serviceGroup->save();

        if ($request->has('servicegroup_members') && $serviceGroup) {
            // Extract members from the request
            $members = $request->input('servicegroup_members');
            $members = ServiceGroup::whereIn('servicegroup_name',$members)->pluck('id'); // get the id from the servicegroup table

            // Prepare data for insertion
            $data = collect($members)->map(function ($member) use ($serviceGroup) {
                return ['servicegroup' => $serviceGroup->id, 'members' => $member];
            });

            // Update or create rows in the servicegroup_members table
            $data->each(function ($memberData) {
                ServiceGroupMember::updateOrCreate(
                    ['servicegroup' => $memberData['servicegroup'], 'members' => $memberData['members']],
                    $memberData
                );
            });
        }




        // Return a JSON response with a 200 OK status code
        return response()->json([
            'message' => 'Service Group updated successfully',
            'service_group' => $serviceGroup,
        ], 200);
    }
    /**
     * @OA\Patch(
     *      path="/api/config/servicegroup/{serviceGroup}",
     *      operationId="patchServiceGroup",
     *      tags={"ServiceGroups"},
     *      summary="Patch an existing ServiceGroup in Emca Monitor",
     *      description="Partially updates an existing ServiceGroup with the provided parameters.",
     *      security={{"basicAuth":{}}},
     *      @OA\Parameter(
     *          name="serviceGroup",
     *          description="ID or name of the ServiceGroup to be updated",
     *          required=true,
     *          in="path",
     *          @OA\Schema(type="string")
     *      ),
     *      @OA\RequestBody(
     *          required=true,
     *          description="Updated ServiceGroup details",
     *          @OA\JsonContent(
     *              required={"servicegroup_name"},
     *              @OA\Property(property="servicegroup_name", type="string", example="updated_service_group"),
     *              @OA\Property(property="file_id", type="string", example="/opt/naemon/etc/naemon/conf.d/localhost.cfg"),
     *              @OA\Property(property="action_url", type="string", example="updated_notify-service-by-email"),
     *              @OA\Property(property="alias", type="integer", example="alias"),
     *              @OA\Property(property="servicegroup_members", type="array",  @OA\Items(type="string", example="updated_serviceGroup_member_id")),
     *              @OA\Property(property="members", type="array", @OA\Items(type="string", example="updated_member_id")),
     *              @OA\Property(property="notes", type="string", example="Updated notes"),
     *              @OA\Property(property="notes_url", type="string", example="http://example.com/updated_notes"),
     *              @OA\Property(property="register", type="boolean", example=true),
     *          ),
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Service Group patched successfully",
     *          @OA\JsonContent(
     *              @OA\Property(property="message", type="string", example="Service Group patched successfully"),
     *              @OA\Property(property="service_group", type="object"),
     *          ),
     *      ),
     *      @OA\Response(
     *          response=400,
     *          description="Bad request",
     *          @OA\JsonContent(
     *              @OA\Property(property="message", type="string", example="Validation error"),
     *              @OA\Property(property="errors", type="object", example={"Service Group description": {"The Service Group description field is required."}}),
     *          ),
     *      ),
     *      @OA\Response(
     *          response=404,
     *          description="Service Group not found",
     *          @OA\JsonContent(
     *              @OA\Property(property="message", type="string", example="Service Group not found"),
     *          ),
     *      ),
     * )
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */


    public function patchServiceGroup(Request $request, $serviceGroup)
    {
         // Validate the incoming request data
         $validatedData = $request->validate([
            'servicegroup_name' => 'sometimes|string|unique:servicegroup',
            'file_id' => 'sometimes|string',
            'register' => 'nullable|boolean',
            'servicegroup_members' => 'nullable|array',
            'servicegroup_members.*' => 'string',
        ]);

        // Find the servicegroup by ID or Name
        $serviceGroup = ServiceGroup::where('id', $serviceGroup)
                ->orWhere('servicegroup_name', $serviceGroup)
                ->first();
        // Check if the servicegroup exists
        if (!$serviceGroup) {
            return response()->json([
                'message' => 'Service Group not found',
            ], 404);
        }

        if( $request->has('file_id'))
        {
            $fileId = \DB::table('file_tbl')->select('id')->where('file_name', $request->file_id)->first();
            if ($fileId) {

                $serviceGroup->file_id = $fileId->id;
            }
            else
            {
                return response()->json(['message' => 'file id not found'], 404);
            }
        }
        // Update the servicegroup with the new data
        $serviceGroup->servicegroup_name = $request->has('servicegroup_name') ? $request->servicegroup_name : $serviceGroup->servicegroup_name;
        $serviceGroup->alias = $request->has('alias') ? $request->alias : $serviceGroup->alias;
        $serviceGroup->notes = $request->has('notes') ? $request->notes : $serviceGroup->notes;
        $serviceGroup->notes_url = $request->has('notes_url') ? $request->notes_url :  $serviceGroup->notes_url;
        $serviceGroup->action_url = $request->has('action_url') ? $request->action_url : $serviceGroup->action_url;
        $serviceGroup->register = $request->has('register') ? $request->register : $serviceGroup->register;
        $serviceGroup->save();

        if ($request->has('servicegroup_members') && $serviceGroup) {
            // Extract members from the request
            $members = $request->input('servicegroup_members');
            $members = ServiceGroup::whereIn('servicegroup_name',$members)->pluck('id'); // get the id from the servicegroup table

            // Prepare data for insertion
            $data = collect($members)->map(function ($member) use ($serviceGroup) {
                return ['servicegroup' => $serviceGroup->id, 'members' => $member];
            });

            // Update or create rows in the servicegroup_members table
            $data->each(function ($memberData) {
                ServiceGroupMember::updateOrCreate(
                    ['servicegroup' => $memberData['servicegroup'], 'members' => $memberData['members']],
                    $memberData
                );
            });
        }




        // Return a JSON response with a 200 OK status code
        return response()->json([
            'message' => 'Service Group updated successfully',
            'service_group' => $serviceGroup,
        ], 200);
    }

    /**
     * @OA\Delete(
     *      path="/api/config/servicegroup/{id}",
     *      operationId="deleteServiceGroup",
     *      tags={"ServiceGroups"},
     *      summary="Delete a specific service group in Emca Monitor",
     *      description="Deletes a service group by ID or name.",
     *      security={{"basicAuth":{}}},
     *      @OA\Parameter(
     *          name="id",
     *          in="path",
     *          required=true,
     *          description="ID or name of the service group to delete",
     *          @OA\Schema(
     *              type="string",
     *          ),
     *      ),
     *      @OA\Response(
     *          response=204,
     *          description="Service group deleted successfully",
     *      ),
     *      @OA\Response(
     *          response=404,
     *          description="Service group not found",
     *          @OA\JsonContent(
     *              type="object",
     *              @OA\Property(property="error", type="string", example="Service group not found"),
     *          ),
     *      ),
     * )
     *
     * @param int|string $id
     * @return JsonResponse
     */
    public function deleteServiceGroup($id)
    {
        // Retrieve the service group using the ServiceGroup model
        $serviceGroup = ServiceGroup::where('id', $id)
            ->orWhere('servicegroup_name', $id)
            ->first();

        // Check if the service group is found
        if (!$serviceGroup) {
            return response()->json(['error' => 'Service group not found'], 404);
        }

        // Delete the service group
        $serviceGroup->delete();

        // Return a JSON response
        return response()->json([], 204);
    }


}
