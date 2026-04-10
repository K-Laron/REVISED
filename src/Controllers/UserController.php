<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Controllers\Concerns\InteractsWithApi;
use App\Controllers\Concerns\RendersViews;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Helpers\Validator;
use App\Middleware\CsrfMiddleware;
use App\Services\UserService;
use App\Support\Pagination;
use RuntimeException;

class UserController
{
    use InteractsWithApi;
    use RendersViews;

    public function __construct(
        private readonly UserService $users
    ) {
    }

    public function index(Request $request): Response
    {
        $authUser = $this->currentUser($request);

        return $this->renderAppView('users.index', [
            'title' => 'User Management',
            'extraCss' => ['/assets/css/users.css'],
            'extraJs' => ['/assets/js/users.js'],
            'csrfToken' => CsrfMiddleware::token(),
            'roles' => $this->users->roles(),
            'canManageRolePermissions' => (($authUser['role_name'] ?? null) === 'super_admin'),
        ]);
    }

    public function create(Request $request): Response
    {
        return $this->renderAppView('users.create', [
            'title' => 'Create User',
            'extraCss' => ['/assets/css/users.css'],
            'extraJs' => ['/assets/js/users.js'],
            'csrfToken' => CsrfMiddleware::token(),
            'roles' => $this->users->roles(),
        ]);
    }

    public function show(Request $request, string $id): Response
    {
        try {
            $user = $this->users->get((int) $id);
        } catch (RuntimeException) {
            return Response::redirect('/users');
        }

        return $this->renderAppView('users.show', [
            'title' => trim(($user['first_name'] ?? '') . ' ' . ($user['last_name'] ?? '')),
            'extraCss' => ['/assets/css/users.css'],
            'extraJs' => ['/assets/js/users.js'],
            'csrfToken' => CsrfMiddleware::token(),
            'userRecord' => $user,
            'roles' => $this->users->roles(),
        ]);
    }

    public function list(Request $request): Response
    {
        $page = Pagination::page($request->query('page'));
        $perPage = Pagination::perPage($request->query('per_page'), 20);
        $result = $this->users->list($request->query(), $page, $perPage);

        return $this->paginatedSuccess($result, $page, $perPage, 'Users retrieved successfully.');
    }

    public function get(Request $request, string $id): Response
    {
        try {
            $user = $this->users->get((int) $id);
        } catch (RuntimeException $exception) {
            return Response::error(404, 'NOT_FOUND', $exception->getMessage());
        }

        return Response::success($user, 'User retrieved successfully.');
    }

    public function store(Request $request): Response
    {
        $validator = (new Validator($request->body()))->rules($this->rules(true));
        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        $authUserId = $this->currentUserId($request);

        try {
            $user = $this->users->create($request->body(), $authUserId, $request);
        } catch (RuntimeException $exception) {
            return Response::error(409, 'USER_CREATE_BLOCKED', $exception->getMessage());
        }

        return Response::success($user, 'User created successfully.');
    }

    public function update(Request $request, string $id): Response
    {
        $validator = (new Validator($request->body()))->rules($this->rules(false));
        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        $authUser = $this->currentUser($request);
        $authUserId = (int) ($authUser['id'] ?? 0);

        try {
            $user = $this->users->update((int) $id, $request->body(), $authUserId, $request);
        } catch (RuntimeException $exception) {
            return Response::error(409, 'USER_UPDATE_BLOCKED', $exception->getMessage());
        }

        if ((int) $authUser['id'] === (int) $id) {
            Session::put('auth.user', $user);
        }

        return Response::success($user, 'User updated successfully.');
    }

    public function destroy(Request $request, string $id): Response
    {
        try {
            $this->users->delete((int) $id, $this->currentUserId($request), $request);
        } catch (RuntimeException $exception) {
            return Response::error(409, 'USER_DELETE_BLOCKED', $exception->getMessage());
        }

        return Response::success([], 'User deleted successfully.');
    }

    public function restore(Request $request, string $id): Response
    {
        $user = $this->users->restore((int) $id, $this->currentUserId($request), $request);

        return Response::success($user, 'User restored successfully.');
    }

    public function changeRole(Request $request, string $id): Response
    {
        $validator = (new Validator($request->body()))->rules([
            'role_id' => 'required|integer|exists:roles,id',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        $user = $this->users->changeRole((int) $id, (int) $request->body('role_id'), $this->currentUserId($request), $request);

        return Response::success($user, 'User role updated successfully.');
    }

    public function resetPassword(Request $request, string $id): Response
    {
        $validator = (new Validator($request->body()))->rules([
            'password' => 'required|string|min:8|max:255|strong_password|confirmed',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        $this->users->resetPassword((int) $id, (string) $request->body('password'), $this->currentUserId($request), $request);

        return Response::success([], 'Password reset successfully.');
    }

    public function sessions(Request $request, string $id): Response
    {
        try {
            $sessions = $this->users->sessions((int) $id);
        } catch (RuntimeException $exception) {
            return Response::error(404, 'NOT_FOUND', $exception->getMessage());
        }

        return Response::success($sessions, 'User sessions retrieved successfully.');
    }

    public function destroySession(Request $request, string $id, string $sessionId): Response
    {
        try {
            $this->users->destroySession((int) $id, (int) $sessionId, $this->currentUserId($request), $request);
        } catch (RuntimeException $exception) {
            return Response::error(404, 'NOT_FOUND', $exception->getMessage());
        }

        return Response::success([], 'User session terminated successfully.');
    }

    private function rules(bool $creating): array
    {
        $rules = [
            'role_id' => 'required|integer|exists:roles,id',
            'email' => 'required|email|max:255',
            'first_name' => 'required|string|min:2|max:100',
            'last_name' => 'required|string|min:2|max:100',
            'middle_name' => 'nullable|string|max:100',
            'phone' => 'nullable|phone_ph',
            'address_line1' => 'nullable|string|max:255',
            'address_line2' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:100',
            'province' => 'nullable|string|max:100',
            'zip_code' => 'nullable|string|regex:/^[A-Za-z0-9-]+$/',
            'is_active' => 'nullable|boolean',
            'email_verified' => 'nullable|boolean',
            'force_password_change' => 'nullable|boolean',
        ];

        if ($creating) {
            $rules['password'] = 'required|string|min:8|max:255|strong_password|confirmed';
        }

        return $rules;
    }
}
