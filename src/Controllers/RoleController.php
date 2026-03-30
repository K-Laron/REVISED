<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Controllers\Concerns\InteractsWithApi;
use App\Core\Request;
use App\Core\Response;
use App\Helpers\Validator;
use App\Services\UserService;
use RuntimeException;

class RoleController
{
    use InteractsWithApi;

    private UserService $users;

    public function __construct()
    {
        $this->users = new UserService();
    }

    public function list(Request $request): Response
    {
        return Response::success($this->users->roles(), 'Roles retrieved successfully.');
    }

    public function permissions(Request $request, string $id): Response
    {
        return Response::success([
            'role_id' => (int) $id,
            'permissions' => $this->users->rolePermissions((int) $id),
            'catalog' => $this->users->permissionCatalog(),
        ], 'Role permissions retrieved successfully.');
    }

    public function updatePermissions(Request $request, string $id): Response
    {
        $validator = (new Validator($request->body()))->rules([
            'permission_ids' => 'required|array|min:1',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        $authUserId = $this->currentUserId($request);

        try {
            $result = $this->users->updateRolePermissions((int) $id, $request->body('permission_ids', []), $authUserId, $request);
        } catch (RuntimeException $exception) {
            return Response::error(409, 'ROLE_PERMISSION_UPDATE_BLOCKED', $exception->getMessage());
        }

        return Response::success($result, 'Role permissions updated successfully.');
    }
}
