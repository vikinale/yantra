<?php
namespace Core\Controllers;

use Core\TokenManager;
use Exception;
use InvalidArgumentException;
use JetBrains\PhpStorm\NoReturn;

abstract class APIBaseController
{
    /**
     * @var array Holds the HTTP status codes and corresponding messages.
     */
    protected array $statusCodes = [
        200 => 'OK',
        201 => 'Created',
        400 => 'Bad Request',
        401 => 'Unauthorized',
        403 => 'Forbidden',
        404 => 'Not Found',
        500 => 'Internal Server Error',
    ];

    /**
     * APIBaseController constructor.
     *
     * @throws Exception
     */
    public function __construct()
    {
    }

    /**
     * Send a JSON response with a status code and data.
     *
     * @param array|object $data The data to return in the response.
     * @param int $statusCode The HTTP status code.
     * @param array $headers Additional headers for the response.
     */
    #[NoReturn] protected function jsonResponse($data, int $statusCode = 200, array $headers = []): void
    {
        header('Content-Type: application/json', true, $statusCode);

        foreach ($headers as $key => $value) {
            header("$key: $value");
        }

        echo json_encode([
            'status' => $statusCode,
            'message' => $this->statusCodes[$statusCode] ?? 'Unknown Status',
            'data' => $data,
        ]);
        exit;
    }

    /**
     * Send an error response in JSON format.
     *
     * @param string $errorMessage The error message to return.
     * @param int $statusCode The HTTP status code.
     * @param array $errors Additional error details, if any.
     */
    #[NoReturn] protected function jsonErrorResponse(string $errorMessage, int $statusCode = 400, array $errors = []): void
    {
        $this->jsonResponse([
            'error' => $errorMessage,
            'details' => $errors,
        ], $statusCode);
    }

    /**
     * Validate incoming JSON request data.
     *
     * @param array $requiredFields The required fields to check.
     * @return array The validated data.
     * @throws InvalidArgumentException If a required field is missing.
     */
    protected function validateJsonRequest(array $requiredFields): array
    {
        $input = json_decode(file_get_contents('php://input'), true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->jsonErrorResponse('Invalid JSON payload', 400);
        }

        foreach ($requiredFields as $field) {
            if (!isset($input[$field])) {
                $this->jsonErrorResponse("Missing required field: $field", 400);
            }
        }

        return $input;
    }

    /**
     * Authenticate API request using different authorization methods.
     *
     * @param string $method The type of authorization ('bearer', 'basic', 'api_key', etc.).
     * @param string|null $expectedToken Optional: expected token (for bearer token or API key).
     * @throws Exception If authentication fails.
     */
    protected function authenticateRequest(string $method, ?string $expectedToken = null): void
    {
        switch (strtolower($method)) {
            case 'bearer':
                $this->authenticateBearerToken();
                break;

            case 'basic':
                $this->authenticateBasicAuth();
                break;

            case 'api_key':
                $this->authenticateApiKey();
                break;

            default:
                $this->jsonErrorResponse('Invalid or unsupported authentication method', 400);
        }
    }

    /**
     * Authenticate using Bearer Token.
     *
     */
    private function authenticateBearerToken(): void
    {
        $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        $t = new TokenManager();
        $token = strpos($authHeader, 'Bearer ') !== 0 || trim(str_replace('Bearer ', '', $authHeader));

        if ( is_null($t->validateBearerToken($token))) {
            $this->jsonErrorResponse('Unauthorized: Invalid Bearer Token', 401);
        }
    }

    /**
     * Authenticate using Basic Authentication.
     *
     * @throws Exception If the username/password is invalid or missing.
     */
    private function authenticateBasicAuth(): void
    {
        // Check for the Authorization header
        $authorizationHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        if (!str_starts_with($authorizationHeader, 'Basic ')) {
            // Send WWW-Authenticate header to indicate the client needs to authenticate
            header('WWW-Authenticate: Basic realm="Access restricted"');
            $this->jsonErrorResponse('Unauthorized: Missing or invalid Authorization header', 401);
        }
        else{
            // Extract the Base64-encoded part of the Authorization header
            $base64Credentials = trim(str_replace('Basic ','',$authorizationHeader));

            // Decode the Base64 string to get "username:password"
            $credentials = base64_decode($base64Credentials);
            if ($credentials === false) {
                $this->jsonErrorResponse('Unauthorized: Invalid Base64 encoding', 401);
            }
            else{
                // Split the decoded string into username and password
                list($username, $password) = explode(':', $credentials, 2);

                // Validate the username and password
                if (!$this->isValidBasicAuthCredentials($username, $password)) {
                    // Send the WWW-Authenticate header if credentials are invalid
                    header('WWW-Authenticate: Basic realm="Access restricted"');
                    $this->jsonErrorResponse('Unauthorized: Invalid credentials', 401);
                }
            }
        }
    }

    /**
     * Authenticate using API Key.
     *
     * @param string|null $expectedApiKey The expected API Key.
     * @throws Exception If the API Key is invalid or missing.
     */
    protected function authenticateApiKey(): void{
        $api_key= $_SERVER['API_KEY'] ?? '';
        $t = new TokenManager();

        if ( is_null($t->validateAPIKey($api_key))) {
            $this->jsonErrorResponse('Unauthorized: Invalid API Key', 401);
        }
    }

    /**
     * Placeholder for validating Basic Auth credentials.
     *
     * @param string $username
     * @param string $password
     * @return bool
     */
    protected abstract  function isValidBasicAuthCredentials(string $username, string $password): bool;

}
