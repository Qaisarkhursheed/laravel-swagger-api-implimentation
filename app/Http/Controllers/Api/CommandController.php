<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use App\Models\Command;
use Illuminate\Support\Facades\URL;


class CommandController extends Controller
{
    /**
         * @OA\Get(
            *      path="/api/config/command/{identifier}",
            *      operationId="getCommandByIdentifier",
            *      tags={"commands"},
            *      summary="Get a command by ID or name",
            *      description="Retrieves a specific command by its ID or name.",
            *      security={{"basicAuth":{}}},
            *      @OA\Parameter(
            *          name="identifier",
            *          in="path",
            *          description="ID or name of the command",
            *          @OA\Schema(
            *              type="string",
            *              example=""
            *          ),
            *      ),
         *      @OA\Response(
         *          response=200,
         *          description="List of commands",
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
    public function getAllCommands(Request $request, $identifier = null): JsonResponse
    {
        $identifier = urldecode($identifier);

        // Retrieve the command(s) based on the provided identifier, if any
        $commandsQuery = Command::query();

        // If identifier is provided, filter commands by ID or name
        if ($identifier !== null &&  $identifier !== '{identifier}' && $identifier !== "") {
            $commandsQuery->where(function ($query) use ($identifier) {
                $query->where('id', $identifier)
                    ->orWhere('command_name', $identifier);
            });
        }
        else {
            $identifier = null;
        }
        // Retrieve commands
        $commands = $commandsQuery->get();
        // Check if any commands found
        if ($commands->isEmpty()) {
            return response()->json(['error' => 'Command(s) not found'], 404);
        }

        // Prepare the response data
        if (!$identifier) {
            // If no identifier provided, return an array of objects
            $responseData = $commands->map(function ($command) {
                return [
                    'name' => $command->command_name,
                    'resource' => URL::to('/api/config/command/' . $command->command_name)
                ];
            });
        } else {
            // If identifier provided, return just the object
            $responseData = $commands->first();
        }

        // Return a JSON response with the list of commands
        return response()->json($responseData, 200);
    }


    /**
     * @OA\Post(
     *      path="/api/config/command",
     *      operationId="createCommand",
     *      tags={"commands"},
     *      summary="Create a new command in Emca Monitor",
     *      description="Creates a new command with the provided parameters.",
     *      security={{"basicAuth":{}}},
     *      @OA\RequestBody(
     *          required=true,
     *          description="command details",
     *         @OA\JsonContent(
    *             required={"command_line", "command_name", "file_id"},
    *              @OA\Property(property="command_name", type="string", example="notify-host-by-email"),
    *              @OA\Property(property="command_line", type="string", example="$USER1$/check_ftp -H $HOSTADDRESS$ $ARG1$"),
    *              @OA\Property(property="file_id", type="string", example="/opt/naemon/etc/naemon/conf.d/commands.cfg"),
    *              @OA\Property(property="register", type="boolean", example=false),
    *          ),
     *      ),
     *      @OA\Response(
     *          response=201,
     *          description="command created successfully",
     *          @OA\JsonContent(
     *              @OA\Property(property="message", type="string", example="command created successfully"),
     *              @OA\Property(property="command", type="object"),
     *          ),
     *      ),
     *      @OA\Response(
     *          response=400,
     *          description="Bad request",
     *          @OA\JsonContent(
     *              @OA\Property(property="message", type="string", example="Validation error"),
     *              @OA\Property(property="errors", type="object", example={"command_description": {"The command_description field is required."}}),
     *          ),
     *      ),
     * )
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function createCommand(Request $request): JsonResponse
    {
        // Validate the incoming request data
        $validatedData = $request->validate([
            'command_line' => 'required|string',
            'command_name' => 'required|string|unique:command',
            'file_id' => 'required|string',
            'register' => 'nullable|boolean',
        ]);

        if( $request->file_id)
        {
            $fileId = \DB::table('file_tbl')->select('id')->where('file_name', $request->file_id)->first();
            if ($fileId) {
                $validatedData['file_id'] = $fileId->id;
            }
            else
                return response()->json(['message' => 'file id not found'], 404);
        }
        // Create a new command using the command model
        $command = Command::create($validatedData);

        // Return a JSON response with a 201 Created status code
        return response()->json([
            'message' => 'Command created successfully',
            'command' => $command,
        ], 201);
    }

    /**
     * @OA\Put(
     *      path="/api/config/command/{id}",
     *      operationId="updateCommand",
     *      tags={"commands"},
     *      summary="Update a command in Emca Monitor",
     *      description="Updates an existing command with the provided parameters.",
     *      security={{"basicAuth":{}}},
     *      @OA\Parameter(
     *          name="id",
     *          in="path",
     *          required=true,
     *          description="ID of the command to be updated",
     *          @OA\Schema(type="string")
     *      ),
     *      @OA\RequestBody(
     *          required=true,
     *          description="command details",
     *          @OA\JsonContent(
     *             required={"command_line", "command_name", "file_id"},
    *              @OA\Property(property="command_name", type="string", example="notify-host-by-email"),
    *              @OA\Property(property="command_line", type="string", example="$USER1$/check_ftp -H $HOSTADDRESS$ $ARG1$"),
    *              @OA\Property(property="file_id", type="string", example="/opt/naemon/etc/naemon/conf.d/commands.cfg"),
    *              @OA\Property(property="register", type="boolean", example=true),
     *          ),
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="command updated successfully",
     *          @OA\JsonContent(
     *              @OA\Property(property="message", type="string", example="command updated successfully"),
     *              @OA\Property(property="command", type="object"),
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
     *          description="command not found",
     *          @OA\JsonContent(
     *              @OA\Property(property="message", type="string", example="command not found"),
     *          ),
     *      ),
     * )
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function updateCommand(Request $request, $id): JsonResponse
    {


        // Find the command by ID

        if ($id) {
             $command = Command::where('id', $id)->orWhere('command_name',"=", $id)->first();
        }

        // Check if the command exists
        if (!$command) {
            return response()->json(['message' => 'Command not found'], 404);
        }

        if( $request->file_id)
        {
            $fileId = \DB::table('file_tbl')->select('id')->where('file_name', $request->file_id)->first();
            if ($fileId) {
                $validatedData['file_id'] = $fileId->id;
            }
            else
                return response()->json(['message' => 'file id not found'], 404);
        }

        // Update the command attributes
        $command->update($validatedData);

        // Return a JSON response with a 200 OK status code
        return response()->json([
            'message' => 'Command updated successfully',
            'command' => $command,
        ], 200);
    }
    /**
     * @OA\Patch(
     *      path="/api/config/command/{command}",
     *      operationId="patchCommand",
     *      tags={"commands"},
     *      summary="Patch/update a command in Emca Monitor",
     *      description="Updates an existing command with the provided parameters.",
     *      security={{"basicAuth":{}}},
     *      @OA\Parameter(
     *          name="command",
     *          in="path",
     *          required=true,
     *          description="ID or name of the command to be updated",
     *          @OA\Schema(type="string")
     *      ),
     *      @OA\RequestBody(
     *          required=true,
     *          description="Command details",
     *          @OA\JsonContent(
     *             @OA\Property(property="command_line", type="string", example="$USER1$/check_ftp -H $HOSTADDRESS$ $ARG1$"),
     *             @OA\Property(property="command_name", type="string", example="notify-host-by-email"),
     *             @OA\Property(property="file_id", type="string", example="/opt/naemon/etc/naemon/conf.d/commands.cfg"),
     *             @OA\Property(property="register", type="boolean", example=true),
     *          ),
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Command patched successfully",
     *          @OA\JsonContent(
     *              @OA\Property(property="message", type="string", example="Command patched successfully"),
     *              @OA\Property(property="command", type="object"),
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
     *          description="Command not found",
     *          @OA\JsonContent(
     *              @OA\Property(property="message", type="string", example="Command not found"),
     *          ),
     *      ),
     * )
     *
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function patchCommand(Request $request, $command): JsonResponse
    {
        // Find the command by ID
        $command = Command::where('id', $command)->orWhere('command_name',"=", $command)->first();

        // Check if the command exists
        if (!$command) {
            return response()->json(['message' => 'Command not found'], 404);
        }

        // Validate the incoming request data
        $validatedData = $request->validate([
            'command_line' => 'string',
            'command_name' => 'string|unique:command',
            'file_id' => 'string',
            'register' => 'nullable|boolean',
        ]);

        // Update command data if provided
        if ($request->has('command_line')) {
            $command->command_line = $validatedData['command_line'];
        }
        if ($request->has('command_name')) {
            // Check uniqueness if command_name is being updated
            if (Command::where('command_name', $validatedData['command_name'])->where('id', '!=', $command)->exists()) {
                return response()->json(['message' => 'Command name already exists'], 422);
            }
            $command->command_name = $validatedData['command_name'];
        }
        if ($request->has('file_id')) {
            // Retrieve the file ID if file_id is provided
            $fileId = \DB::table('file_tbl')->select('id')->where('file_name', $validatedData['file_id'])->first();
            if ($fileId) {
                $command->file_id = $fileId->id;
            } else {
                return response()->json(['message' => 'File ID not found'], 404);
            }
        }
        if ($request->has('register')) {
            $command->register = $validatedData['register'];
        }

        // Save the updated command
        $command->save();

        // Return a JSON response with a 200 OK status code
        return response()->json([
            'message' => 'Command patched successfully',
            'command' => $command,
        ], 200);
    }


    /**
     * @OA\Delete(
     *      path="/api/config/command/{id}",
     *      operationId="deleteCommand",
     *      tags={"commands"},
     *      summary="Delete a command in op5 Monitor",
     *      description="Deletes an existing command.",
     *      security={{"basicAuth":{}}},
     *      @OA\Parameter(
     *          name="id",
     *          in="path",
     *          required=true,
     *          description="ID of the command to be deleted",
     *          @OA\Schema(type="string")
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="command deleted successfully",
     *          @OA\JsonContent(
     *              @OA\Property(property="message", type="string", example="command deleted successfully"),
     *          ),
     *      ),
     *      @OA\Response(
     *          response=404,
     *          description="command not found",
     *          @OA\JsonContent(
     *              @OA\Property(property="message", type="string", example="command not found"),
     *          ),
     *      ),
     * )
     *
     * @param int $id
     * @return JsonResponse
     */
    public function deleteCommand($identifier): JsonResponse
    {
        // Find the command by ID or name
        $command = Command::where('id', $identifier)->orWhere('command_name', $identifier)->first();

        // Check if the command exists
        if (!$command) {
            return response()->json(['message' => 'Command not found'], 404);
        }

        // Delete the command
        $command->delete();

        // Return a JSON response with a 200 OK status code
        return response()->json([
            'message' => 'command deleted successfully',
        ], 200);
    }

}
