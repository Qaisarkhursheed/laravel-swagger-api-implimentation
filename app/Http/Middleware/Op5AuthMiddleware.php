<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Yaml\Yaml;
use Illuminate\Support\Str;

class Op5AuthMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle($request, Closure $next): Response
    {
        // Extract credentials from the Authorization header
        $authorizationHeader = $request->header('Authorization');

        if (!$authorizationHeader || !Str::startsWith($authorizationHeader, 'Basic ')) {
            return response('Unauthorized.', 401);
        }

        // Decode the base64-encoded credentials
        $credentials = base64_decode(Str::after($authorizationHeader, 'Basic '));

        // Extract username and password
        list($username, $password) = explode(':', $credentials, 2);

        // Load user data from the YAML file
        $userData = Yaml::parseFile(storage_path('auth_users.yml'));

        // Check if the user exists and credentials match
        if ($this->authenticateUser($username, $password, $userData)) {
            return $next($request);
        }

        // Unauthorized response if authentication fails
        return response('Unauthorized.', 401);
    }
    private function authenticateUser($username, $password, $userData)
    {
        // Check if the user exists in the YAML file
        if (isset($userData[$username])) {
            $storedPassword = $userData[$username]['password'];
            $algo = $userData[$username]['password_algo'];
            // Compare the provided password with the stored password
            if (self::valid_password($password, $storedPassword,$algo)) {
                // You may perform additional checks here (e.g., user groups, modules)
                return true; // Authentication successful
            }
        }
        return false; // Authentication failed
    }
    public static function valid_password($pass, $hash, $algo = 'crypt') {
        $hash = str_replace("\n", '', $hash);
		if ($algo === false || !is_string($algo))
			return false;
		if (empty($pass) || empty($hash))
			return false;
		if (!is_string($pass) || !is_string($hash))
			return false;

		switch ($algo) {
		case 'sha1':
			return sha1($pass) === $hash;

		case 'b64_sha1':
			// Passwords can be one of
			// ... base64 encoded raw sha1
			return base64_encode(sha1($pass, true)) === $hash;

		case 'crypt':
			// ... crypt() or password_hash() encrypted
			return password_verify($pass, $hash);

		case 'plain':
			// ... plaintext (stupid, but true)
			return $pass === $hash;

		case 'apr_md5':
			// ... or a mad and weird aberration of md5
			return self::apr_md5_validate($pass, $hash);
		default:
			return false;
		}

		// not-reached
		return false;
	}
}
