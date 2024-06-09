<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Routing\Controller as BaseController;

/**
 * @OA\SecurityScheme(
 *      securityScheme="basicAuth",
 *      in="header",
 *      name="Authorization",
 *      type="http",
 *      scheme="basic",
 * )
 *
 * @OA\Info(
 *     title="Emca",
 *     version="1.0"
 * )
 */

class Controller extends BaseController
{
    use AuthorizesRequests, ValidatesRequests;
}
