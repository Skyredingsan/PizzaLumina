<?php

declare(strict_types=1);

namespace App\Modules\User\Controllers;

use App\Modules\User\Enums\UserRole;
use App\Modules\User\Models\User;
use App\Modules\User\Requests\AdminUserListRequest;
use App\Modules\User\Requests\UpdateUserRoleRequest;
use App\Modules\User\Resources\UserResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

final class AdminUserController
{
    public function index(AdminUserListRequest $request): JsonResponse
    {
        $input = $request->toInput();
        $perPage = $input->perPage;
        $users = User::query()->orderBy('id')->paginate(perPage: $perPage);
        return response()->json(['data' => UserResource::collection(resource: $users), 'meta' => ['current_page' => $users->currentPage(), 'last_page' => $users->lastPage(), 'per_page' => $users->perPage(), 'total' => $users->total()]]);
    }

    public function updateRole(UpdateUserRoleRequest $request, User $user): JsonResponse
    {
        $newRole = UserRole::from(value: $request->string(key: 'role')->toString());
        $current = Auth::guard('api')->user();
        if ($current instanceof User && $current->is(model: $user)) {
            return response()->json(['message' => __(key: 'api.cannot_change_own_role')], 409);
        }
        if ($user->isAdmin() && $newRole !== UserRole::Admin && User::query()->where(column: 'role', operator: UserRole::Admin)->count() <= 1) {
            return response()->json(['message' => __(key: 'api.last_admin')], 409);
        }
        $user->update(attributes: ['role' => $newRole]);
        return response()->json(['data' => UserResource::make($user->refresh())]);
    }
}
