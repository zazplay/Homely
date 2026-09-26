<?php

namespace App\Http\Controllers\Api;

use App\Actions\Auth\AuthenticateUser;
use App\Actions\Auth\RegisterUser;
use App\Data\Auth\AuthTokenData;
use App\Data\Auth\LoginData;
use App\Data\Auth\RegisterData;
use App\Data\UserData;
use App\Http\Controllers\Controller;
use App\Models\User;
use Dedoc\Scramble\Attributes\BodyParameter;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Attributes\Controllers\Middleware;

class AuthController extends Controller
{
    // Max 6 attempts per minute — protection against password brute force.
    #[Middleware('throttle:6,1')]
    // Scramble (free) can't read laravel-data inputs, so the body is described for Swagger here.
    #[BodyParameter('name', required: true, type: 'string', example: 'Viktor')]
    #[BodyParameter('email', required: true, type: 'string', format: 'email', example: 'viktor@example.com')]
    #[BodyParameter('password', description: 'Min 8 characters', required: true, type: 'string', example: 'secret123')]
    #[BodyParameter('password_confirmation', required: true, type: 'string', example: 'secret123')]
    #[BodyParameter('role', description: '`client` (default) or `realtor`', type: 'string', example: 'realtor')]
    public function register(RegisterData $data, RegisterUser $registerUser): JsonResponse
    {
        $user = $registerUser->handle($data);

        return response()->json($this->issueToken($user), Response::HTTP_CREATED);
    }

    #[Middleware('throttle:6,1')]
    #[BodyParameter('email', required: true, type: 'string', format: 'email', example: 'viktor@example.com')]
    #[BodyParameter('password', required: true, type: 'string', example: 'secret123')]
    public function login(LoginData $data, AuthenticateUser $authenticateUser): JsonResponse
    {
        $user = $authenticateUser->handle($data);

        return response()->json($this->issueToken($user));
    }

    #[Middleware('auth:sanctum')]
    public function me(Request $request): UserData
    {
        return UserData::from($request->user());
    }

    #[Middleware('auth:sanctum')]
    public function logout(Request $request): Response
    {
        // Revokes only the token used for this request; other devices stay logged in.
        $request->user()->currentAccessToken()->delete();

        return response()->noContent();
    }

    private function issueToken(User $user): AuthTokenData
    {
        return new AuthTokenData(
            user: UserData::from($user),
            token: $user->createToken('api')->plainTextToken,
        );
    }
}
