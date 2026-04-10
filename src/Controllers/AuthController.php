<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Controllers\Concerns\InteractsWithApi;
use App\Controllers\Concerns\RendersViews;
use App\Core\Request;
use App\Core\Response;
use App\Helpers\Validator;
use App\Middleware\CsrfMiddleware;
use App\Services\AuthService;
use App\Support\LandingPage;

class AuthController
{
    use InteractsWithApi;
    use RendersViews;

    public function __construct(
        private readonly AuthService $auth
    ) {
    }

    public function showLogin(Request $request): Response
    {
        return $this->renderAuthPage('auth.login', [
            'title' => 'Login',
        ]);
    }

    public function login(Request $request): Response
    {
        $validator = (new Validator($request->body()))->rules([
            'login' => 'required|string|max:255',
            'password' => 'required|string|min:8',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        $result = $this->auth->attemptLogin((string) $request->body('login'), (string) $request->body('password'), $request);
        if (($result['success'] ?? false) !== true) {
            return Response::error(401, 'UNAUTHORIZED', (string) $result['message']);
        }

        $redirect = $this->redirectPathForUser($result['user']);

        return Response::success([
            'user' => $result['user'],
            'redirect' => $redirect,
        ], 'Login successful.');
    }

    public function logout(Request $request): Response
    {
        $this->auth->logout($request);

        return Response::success([
            'redirect' => '/login',
        ], 'Logout successful.');
    }

    public function showForgotPassword(Request $request): Response
    {
        return $this->renderAuthPage('auth.forgot-password', [
            'title' => 'Forgot Password',
        ]);
    }

    public function showForcePasswordChange(Request $request): Response
    {
        $authUser = $request->attribute('auth_user');
        if ((int) ($authUser['force_password_change'] ?? 0) !== 1) {
            return Response::redirect($this->redirectPathForUser($authUser));
        }

        return $this->renderAuthPage('auth.force-password-change', [
            'title' => 'Change Password',
            'currentUser' => $authUser,
        ]);
    }

    public function forgotPassword(Request $request): Response
    {
        $validator = (new Validator($request->body()))->rules([
            'email' => 'required|email|max:255',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        $this->auth->createPasswordReset((string) $request->body('email'));

        return Response::success([], 'If the account exists, a reset link has been prepared.');
    }

    public function showResetPassword(Request $request, string $token): Response
    {
        return $this->renderAuthPage('auth.reset-password', [
            'token' => $token,
            'title' => 'Reset Password',
        ]);
    }

    public function resetPassword(Request $request): Response
    {
        $validator = (new Validator($request->body()))->rules([
            'token' => 'required|string',
            'password' => 'required|string|min:8|strong_password|confirmed',
            'password_confirmation' => 'required|string',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        $reset = $this->auth->resetPassword((string) $request->body('token'), (string) $request->body('password'));
        if (!$reset) {
            return Response::error(400, 'INVALID_RESET_TOKEN', 'The reset token is invalid or has expired.');
        }

        return Response::success([
            'redirect' => '/login',
        ], 'Password reset successful.');
    }

    public function me(Request $request): Response
    {
        return Response::success([
            'user' => $request->attribute('auth_user'),
        ], 'Current user retrieved successfully.');
    }

    public function updateProfile(Request $request): Response
    {
        $validator = (new Validator($request->body()))->rules([
            'first_name' => 'required|string|min:2|max:100',
            'last_name' => 'required|string|min:2|max:100',
            'middle_name' => 'nullable|string|max:100',
            'phone' => 'nullable|phone_ph',
            'address_line1' => 'nullable|string|max:255',
            'address_line2' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:100',
            'province' => 'nullable|string|max:100',
            'zip_code' => 'nullable|string|regex:/^[A-Za-z0-9-]+$/',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        $authUser = $request->attribute('auth_user');
        $user = $this->auth->updateProfile((int) $authUser['id'], $request->body());

        return Response::success([
            'user' => $user,
        ], 'Profile updated successfully.');
    }

    public function changePassword(Request $request): Response
    {
        $validator = (new Validator($request->body()))->rules([
            'current_password' => 'required|string',
            'new_password' => 'required|string|min:8|strong_password|confirmed',
            'new_password_confirmation' => 'required|string',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        $authUser = $request->attribute('auth_user');
        $changed = $this->auth->changePassword(
            (int) $authUser['id'],
            (string) $request->body('current_password'),
            (string) $request->body('new_password')
        );

        if (!$changed) {
            return Response::error(401, 'UNAUTHORIZED', 'The current password is incorrect.');
        }

        return Response::success([
            'redirect' => '/login',
        ], 'Password changed successfully.');
    }

    private function redirectPathForUser(array $user): string
    {
        return LandingPage::forUser($user);
    }

    private function renderAuthPage(string $view, array $data = []): Response
    {
        return $this->renderPublicView($view, $data + [
            'csrfToken' => CsrfMiddleware::token(),
            'extraCss' => ['/assets/css/portal.css'],
        ]);
    }
}
