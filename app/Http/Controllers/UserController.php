<?php

namespace App\Http\Controllers;

use App\Http\Resources\UserResource;
use App\Models\User;
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
     * Display a listing of the resource.
     */
    public function index()
    {
        //
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
     *             @OA\Property(property="status", type="boolean", example=true),
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
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="errors", type="array", items=@OA\Items(type="string"), example={"Invalid email format"})
     *         )
     *     ),
     *     @OA\Response(
     *         response="422",
     *         description="Unprocessable entity, validation error",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="boolean", example=false),
     *             @OA\Property(property="errors", type="array", items=@OA\Items(type="string"), example={"Email is already taken"})
     *         )
     *     ),
     *     @OA\Response(
     *         response="500",
     *         description="Internal server error",
     *         @OA\JsonContent(
     *             type="object",
     *             @OA\Property(property="status", type="boolean", example=false),
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
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
