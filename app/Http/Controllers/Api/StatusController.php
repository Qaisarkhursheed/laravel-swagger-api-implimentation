<?php

namespace App\Http\Controllers\Api;
use App\Http\Controllers\Controller;

use Illuminate\Http\Request;
use App\Services\LiveStatusService;

class StatusController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/status/{type}/{name}",
     *     summary="Get the status based on type and name",
     *     operationId="getStatus",
     *     tags={"Status"},
     *      security={{"basicAuth":{}}},
     *     @OA\Parameter(
     *         name="type",
     *         in="path",
     *         description="Type of status to retrieve",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="name",
     *         in="path",
     *         description="Name of the status to retrieve",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="state",
     *         in="query",
     *         description="Filter by state",
     *         required=false,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="state[]",
     *         in="query",
     *         description="Filter by multiple states",
     *         required=false,
     *         @OA\Schema(type="array", @OA\Items(type="integer"))
     *     ),
     *     @OA\Parameter(
     *         name="hard",
     *         in="query",
     *         description="Filter by hard state",
     *         required=false,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Parameter(
     *         name="name",
     *         in="query",
     *         description="Filter by name",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="format",
     *         in="query",
     *         description="Output format (e.g. xml)",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="hostgroup",
     *         in="query",
     *         description="Filter by hostgroup",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Successful response",
     *         @OA\JsonContent()
     *     ),
     *     @OA\Response(
     *         response=400,
     *         description="Bad request",
     *         @OA\JsonContent()
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Not found",
     *         @OA\JsonContent()
     *     ),
     *     @OA\Response(
     *         response=500,
     *         description="Internal server error",
     *         @OA\JsonContent()
     *     )
     * )
     */
    public function getStatus(Request $request, $type, $name=null)
    {

        if($name==',')
        {
            $name=null;

        }
        if ($request->name == ',') {
            $request->merge(['name' => null]);
        }

        // Create an instance of LiveStatusService
        if ($request->has('name') && $request->query('name') == ',') {
            $name = null;
        }
        $liveStatusService = new LiveStatusService($request);

        // Execute the query and get the result
        $result = $liveStatusService->executeQuery($type, $name);

        // Check if there's an error in the result
        if (isset($result['error'])) {
            // Handle the error response
            return response()->json(['error' => $result['error']], $result['code']);
        }

        // Return the successful response
        return response()->json($result);
    }
}
