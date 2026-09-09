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
        $perPage = min(max((int) $request->integer(key: 'per_page', default: 15), 1), 100);
        $users = User::query()->orderBy('id')->paginate(perPage: $perPage);
        return response()->json(['data' => UserResource::collection(resource: $users), 'meta' => ['current_page' => $users->currentPage(), 'last_page' => $users->lastPage(), 'per_page' => $users->perPage(), 'total' => $users->total()]]);
    }

    public function updateRole(UpdateUserRoleRequest $request, User $user): JsonResponse
    {
        $user->update(attributes: ['role' => UserRole::from(value: $request->string(key: 'role')->toString())]);
        return response()->json(['data' => UserResource::make($user->refresh())]);
    }
}
