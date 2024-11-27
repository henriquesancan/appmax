<?php

namespace App\Http\Controllers;

use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * @OA\Info(title="API", version="1.0")
 */
class UserController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/user",
     *     summary="Display a list of users",
     *     description="Fetches a list of all users in the system.",
     *     operationId="indexUsers",
     *     tags={"User"},
     *     @OA\Response(
     *         response="200",
     *         description="Users retrieved successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="integer", example=200),
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="array",
     *                 @OA\Items(
     *                     type="object",
     *                     @OA\Property(property="name", type="string", example="John Doe"),
     *                     @OA\Property(property="document", type="string", example="12345678901"),
     *                     @OA\Property(property="email", type="string", example="john.doe@example.com")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response="400",
     *         description="Bad request, unable to retrieve users",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="boolean", example=400),
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="errors", type="string", example="An error occurred while retrieving users")
     *         )
     *     ),
     *     @OA\Response(
     *         response="500",
     *         description="Internal server error",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="boolean", example=500),
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="errors", type="string", example="An unexpected error occurred")
     *         )
     *     )
     * )
     */
    public function index(): JsonResponse
    {
        $response = $this->response();

        try {
            $users = User::all();

            $response->status = Response::HTTP_OK;
            $response->success = true;
            $response->data = UserResource::collection($users);
        } catch (Throwable $e) {
            $response->status = Response::HTTP_BAD_REQUEST;
            $response->errors = $e->getMessage();
        } finally {
            return response()->json($response, $response->status, $this->headers());
        }
    }

    /**
     * @OA\Post(
     *     path="/api/user",
     *     summary="Store a new user",
     *     description="Creates a new user in the system with the provided details.",
     *     operationId="storeUser",
     *     tags={"User"},
     *     requestBody={
     *         @OA\MediaType(
     *             mediaType="application/json",
     *             @OA\Schema(
     *                 type="object",
     *                 required={"name", "document", "email", "password"},
     *                 @OA\Property(property="name", type="string", description="The name of the user", example="John Doe"),
     *                 @OA\Property(property="document", type="string", description="The user's document (CPF)", example="12345678901"),
     *                 @OA\Property(property="email", type="string", description="The user's email address", example="john.doe@example.com"),
     *                 @OA\Property(property="password", type="string", description="The user's password", example="secret1234"),
     *                 @OA\Property(property="password_confirmation", type="string", description="Password confirmation", example="secret1234")
     *             )
     *         )
     *     },
     *     @OA\Response(
     *         response="201",
     *         description="User created successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="integer", example=201),
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="John Doe"),
     *                 @OA\Property(property="document", type="string", example="12345678901"),
     *                 @OA\Property(property="email", type="string", example="john.doe@example.com"),
     *                 @OA\Property(property="token", type="string", example="your_token_here")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response="400",
     *         description="Bad request, validation error",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="integer", example=400),
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="errors", type="array", items=@OA\Items(type="string"), example={"Invalid email format"})
     *         )
     *     ),
     *     @OA\Response(
     *         response="422",
     *         description="Unprocessable entity, validation error",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="integer", example=422),
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="errors", type="array", items=@OA\Items(type="string"), example={"Email is already taken"})
     *         )
     *     ),
     *     @OA\Response(
     *         response="500",
     *         description="Internal server error",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="integer", example=500),
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="errors", type="string", example="An unexpected error occurred")
     *         )
     *     )
     * )
     */
    public function store(Request $request): JsonResponse
    {
        DB::beginTransaction();

        $response = $this->response();

        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'document' => 'required|string|cpf|unique:users,document',
                'email' => 'required|email|max:255|unique:users,email',
                'password' => 'required|string|min:8|confirmed',
            ]);

            if ($validator->fails()) {
                throw new \Exception($validator->errors()->first());
            }

            $user = User::create([
                'name' => $validator->validated()['name'],
                'document' => $validator->validated()['document'],
                'email' => $validator->validated()['email'],
                'password' => Hash::make($validator->validated()['password']),
            ]);

            Auth::user();

            $token = $user->createToken('token')->plainTextToken;

            DB::commit();

            $response->status = Response::HTTP_CREATED;
            $response->success = true;
            $response->data = new UserResource($user);
            $response->data = array_merge($response->data->toArray($request), [
                'token' => $token
            ]);
        } catch (ValidationException $e) {
            DB::rollBack();

            $response->status = Response::HTTP_UNPROCESSABLE_ENTITY;
            $response->errors = $e->errors();
        } catch (QueryException $e) {
            DB::rollBack();

            $response->errors = $e->getMessage();
        } catch (Throwable $e) {
            DB::rollBack();

            $response->status = Response::HTTP_BAD_REQUEST;
            $response->errors = $e->getMessage();
        } finally {
            return response()->json($response, $response->status, $this->headers());
        }
    }

    /**
     * @OA\Get(
     *     path="/api/user/{id}",
     *     summary="Display a specific user",
     *     description="Fetches details of a specific user by their ID.",
     *     operationId="showUser",
     *     tags={"User"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID of the user to retrieve",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response="200",
     *         description="User retrieved successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="integer", example=200),
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="name", type="string", example="John Doe"),
     *                 @OA\Property(property="document", type="string", example="12345678901"),
     *                 @OA\Property(property="email", type="string", example="john.doe@example.com")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response="400",
     *         description="Bad request, invalid user ID",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="integer", example=400),
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="errors", type="string", example="Invalid user ID")
     *         )
     *     ),
     *     @OA\Response(
     *         response="404",
     *         description="User not found",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="integer", example=404),
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="errors", type="string", example="User not found")
     *         )
     *     ),
     *     @OA\Response(
     *         response="500",
     *         description="Internal server error",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="integer", example=500),
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="errors", type="string", example="An unexpected error occurred")
     *         )
     *     )
     * )
     */
    public function show(int $id): JsonResponse
    {
        $response = $this->response();

        try {
            $user = User::where('id', $id)->firstOrFail();

            $response->status = Response::HTTP_OK;
            $response->success = true;
            $response->data = new UserResource($user);
        } catch (ModelNotFoundException $e) {
            $response->status = Response::HTTP_NOT_FOUND;
            $response->errors = 'User not found';
        } catch (Throwable $e) {
            $response->status = Response::HTTP_BAD_REQUEST;
            $response->errors = $e->getMessage();
        } finally {
            return response()->json($response, $response->status, $this->headers());
        }
    }

    /**
     * @OA\Put(
     *     path="/api/user/{id}",
     *     summary="Update an existing user",
     *     description="Updates the details of a user by their ID.",
     *     operationId="updateUser",
     *     tags={"User"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="The ID of the user to update",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\MediaType(
     *             mediaType="application/json",
     *             @OA\Schema(
     *                 type="object",
     *                 required={"name", "document", "email"},
     *                 @OA\Property(property="name", type="string", description="The name of the user", example="John Doe"),
     *                 @OA\Property(property="document", type="string", description="The user's document (CPF)", example="12345678901"),
     *                 @OA\Property(property="email", type="string", description="The user's email address", example="john.doe@example.com")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response="200",
     *         description="User updated successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="integer", example=200),
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="John Doe"),
     *                 @OA\Property(property="document", type="string", example="12345678901"),
     *                 @OA\Property(property="email", type="string", example="john.doe@example.com")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response="400",
     *         description="Bad request, validation error",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="integer", example=400),
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="errors", type="string", example="Invalid email format")
     *         )
     *     ),
     *     @OA\Response(
     *         response="404",
     *         description="User not found",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="integer", example=404),
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="errors", type="string", example="User not found")
     *         )
     *     ),
     *     @OA\Response(
     *         response="422",
     *         description="Unprocessable entity, validation error",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="integer", example=422),
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="errors", type="array", items=@OA\Items(type="string"), example={"Email is already taken"})
     *         )
     *     ),
     *     @OA\Response(
     *         response="500",
     *         description="Internal server error",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="integer", example=500),
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="errors", type="string", example="An unexpected error occurred")
     *         )
     *     )
     * )
     */
    public function update(Request $request, int $id): JsonResponse
    {
        DB::beginTransaction();

        $response = $this->response();

        try {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'document' => 'required|string|cpf|unique:users,document,' . $id,
                'email' => 'required|email|max:255|unique:users,email,' . $id,
            ]);

            if ($validator->fails()) {
                throw new \Exception($validator->errors()->first());
            }

            $user = User::where('id', $id)->firstOrFail();

            $user->update([
                'name' => $validator->validated()['name'],
                'document' => $validator->validated()['document'],
                'email' => $validator->validated()['email'],
            ]);

            $response->status = Response::HTTP_OK;
            $response->success = true;
            $response->data = new UserResource($user);
        } catch (ModelNotFoundException $e) {
            DB::rollBack();

            $response->status = Response::HTTP_NOT_FOUND;
            $response->errors = 'User not found';
        } catch (ValidationException $e) {
            DB::rollBack();

            $response->status = Response::HTTP_UNPROCESSABLE_ENTITY;
            $response->errors = $e->errors();
        } catch (QueryException $e) {
            DB::rollBack();

            $response->errors = $e->getMessage();
        } catch (Throwable $e) {
            $response->status = Response::HTTP_BAD_REQUEST;
            $response->errors = $e->getMessage();
        } finally {
            return response()->json($response, $response->status, $this->headers());
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/user/{id}",
     *     summary="Delete a user",
     *     description="Deletes a user by their ID.",
     *     operationId="deleteUser",
     *     tags={"User"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         required=true,
     *         description="The ID of the user to delete",
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response="204",
     *         description="User deleted successfully",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="integer", example=204)
     *         )
     *     ),
     *     @OA\Response(
     *         response="400",
     *         description="Bad request, error during deletion",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="integer", example=400),
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="errors", type="string", example="An unexpected error occurred")
     *         )
     *     ),
     *     @OA\Response(
     *         response="404",
     *         description="User not found",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="integer", example=404),
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="errors", type="string", example="User not found")
     *         )
     *     ),
     *     @OA\Response(
     *         response="500",
     *         description="Internal server error",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="integer", example=500),
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="errors", type="string", example="An unexpected error occurred")
     *         )
     *     )
     * )
     */
    public function destroy(string $id): JsonResponse
    {
        DB::beginTransaction();

        $response = $this->response();

        try {
            $user = User::where('id', $id)->firstOrFail();

            $user->delete();

            DB::commit();

            $response->status = Response::HTTP_NO_CONTENT;
            $response->success = true;
        } catch (ModelNotFoundException $e) {
            DB::rollBack();

            $response->status = Response::HTTP_NOT_FOUND;
            $response->errors = 'User not found';
        } catch (Throwable $e) {
            DB::rollBack();

            $response->status = Response::HTTP_BAD_REQUEST;
            $response->errors = $e->getMessage();
        } finally {
            return response()->json($response, $response->status, $this->headers());
        }
    }
}
