<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUserRequest;
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
     * Store a newly created resource in storage.
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
