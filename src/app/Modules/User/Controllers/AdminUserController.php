<?php

declare(strict_types=1);

namespace App\Modules\User\Controllers;

use App\Modules\User\Enums\UserRole;
use App\Modules\User\Models\User;
use App\Modules\User\Requests\UpdateUserRoleRequest;
use App\Modules\User\Resources\UserResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

final class AdminUserController
{
    public function index(Request $request): JsonResponse
    {
        $perPage = min(max((int) $request->integer('per_page', 15), 1), 100);
        $users = User::query()->orderBy('id')->paginate($perPage);
        return response()->json(['data' => UserResource::collection($users), 'meta' => ['current_page' => $users->currentPage(), 'last_page' => $users->lastPage(), 'per_page' => $users->perPage(), 'total' => $users->total()]]);
    }

    public function updateRole(UpdateUserRoleRequest $request, User $user): JsonResponse
    {
        $user->update(['role' => UserRole::from($request->string('role')->toString())]);
        return response()->json(['data' => UserResource::make($user->refresh())]);
    }
}
