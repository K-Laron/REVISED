<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Controllers\Concerns\InteractsWithApi;
use App\Controllers\Concerns\RendersViews;
use App\Core\Request;
use App\Core\Response;
use App\Core\ExceptionHandler;
use App\Helpers\Validator;
use App\Middleware\CsrfMiddleware;
use App\Services\AdoptionService;
use App\Services\AuthService;
use App\Support\SystemSettings;
use RuntimeException;

class AdopterPortalController
{
    use InteractsWithApi;
    use RendersViews;

    private AdoptionService $adoptions;
    private AuthService $auth;

    public function __construct()
    {
        $this->adoptions = new AdoptionService();
        $this->auth = new AuthService();
    }

    public function landing(Request $request): Response
    {
        if (($response = $this->ensurePortalEnabled($request)) instanceof Response) {
            return $response;
        }

        return $this->renderPortalPage('portal.landing', $request, [
            'title' => 'Adopt',
            'extraCss' => ['/assets/css/portal.css'],
            'extraJs' => ['/assets/js/portal.js'],
            'csrfToken' => CsrfMiddleware::token(),
            'featuredAnimals' => $this->adoptions->featuredAnimals(4),
        ]);
    }

    public function animals(Request $request): Response
    {
        if (($response = $this->ensurePortalEnabled($request)) instanceof Response) {
            return $response;
        }

        $page = max(1, (int) $request->query('page', 1));
        $filters = [
            'search' => trim((string) $request->query('search', '')),
            'species' => trim((string) $request->query('species', '')),
            'gender' => trim((string) $request->query('gender', '')),
            'size' => trim((string) $request->query('size', '')),
        ];
        $result = $this->adoptions->availableAnimals($filters, $page, 12);

        return $this->renderPortalPage('portal.animals', $request, [
            'title' => 'Available Animals',
            'extraCss' => ['/assets/css/portal.css'],
            'extraJs' => ['/assets/js/portal.js'],
            'filters' => $filters,
            'animals' => $result['items'],
            'page' => $page,
            'perPage' => 12,
            'total' => $result['total'],
            'totalPages' => max(1, (int) ceil(max(1, $result['total']) / 12)),
        ]);
    }

    public function animalDetail(Request $request, string $id): Response
    {
        if (($response = $this->ensurePortalEnabled($request)) instanceof Response) {
            return $response;
        }

        try {
            $animal = $this->adoptions->publicAnimalDetail($id);
        } catch (RuntimeException) {
            return Response::redirect('/adopt/animals');
        }

        return $this->renderPortalPage('portal.animal-detail', $request, [
            'title' => $animal['name'] ?: $animal['animal_id'],
            'extraCss' => ['/assets/css/portal.css'],
            'extraJs' => ['/assets/js/portal.js'],
            'animal' => $animal,
        ]);
    }

    public function showRegister(Request $request): Response
    {
        if (($response = $this->ensurePortalEnabled($request)) instanceof Response) {
            return $response;
        }

        return $this->renderRegisterPage($request);
    }

    public function register(Request $request): Response
    {
        if (($response = $this->ensurePortalEnabled($request)) instanceof Response) {
            return $response;
        }

        $validator = (new Validator($request->body()))->rules([
            'email' => 'required|email|max:255|unique:users,email',
            'password' => 'required|string|min:8|strong_password|confirmed',
            'password_confirmation' => 'required|string',
            'first_name' => 'required|string|min:2|max:100',
            'last_name' => 'required|string|min:2|max:100',
            'middle_name' => 'nullable|string|max:100',
            'phone' => 'required|phone_ph',
            'address_line1' => 'required|string|max:255',
            'address_line2' => 'nullable|string|max:255',
            'city' => 'required|string|max:100',
            'province' => 'required|string|max:100',
            'zip_code' => 'required|string|regex:/^[A-Za-z0-9-]+$/',
        ]);

        if ($validator->fails()) {
            if (!$request->expectsJson()) {
                return $this->renderRegisterPage($request, $validator->errors(), $request->body(), 422);
            }

            return $this->validationError($validator->errors());
        }

        try {
            $user = $this->adoptions->registerAdopter($request->body(), $request);
        } catch (RuntimeException $exception) {
            if (!$request->expectsJson()) {
                return $this->renderRegisterPage(
                    $request,
                    ['email' => [$exception->getMessage()]],
                    $request->body(),
                    409
                );
            }

            return Response::error(409, 'ADOPTER_REGISTER_BLOCKED', $exception->getMessage());
        }

        if (!$request->expectsJson()) {
            return Response::redirect('/login');
        }

        return Response::success([
            'user' => $user,
            'redirect' => '/login',
        ], 'Adopter account created successfully.');
    }

    public function showApply(Request $request): Response
    {
        if (($response = $this->ensurePortalEnabled($request)) instanceof Response) {
            return $response;
        }

        $authUser = $request->attribute('auth_user');
        $preferredAnimalId = (int) $request->query('animal_id', 0);

        return $this->renderPortalPage('portal.apply', $request, [
            'title' => 'Apply For Adoption',
            'extraCss' => ['/assets/css/portal.css'],
            'extraJs' => ['/assets/js/portal.js'],
            'csrfToken' => CsrfMiddleware::token(),
            'currentUser' => $authUser,
            'myApplications' => $this->adoptions->myApplications((int) $authUser['id']),
            'availableAnimals' => $this->adoptions->availableAnimals([], 1, 100)['items'],
            'preferredAnimalId' => $preferredAnimalId,
        ]);
    }

    public function apply(Request $request): Response
    {
        if (($response = $this->ensurePortalEnabled($request)) instanceof Response) {
            return $response;
        }

        $authUser = $request->attribute('auth_user');
        $payload = array_merge($request->body(), [
            'valid_id_path' => $request->file('valid_id_path'),
        ]);
        $validator = (new Validator($payload))->rules([
            'animal_id' => 'nullable|integer|exists:animals,id',
            'preferred_species' => 'nullable|in:Dog,Cat',
            'preferred_breed' => 'nullable|string|max:100',
            'preferred_age_min' => 'nullable|integer|between:0,30',
            'preferred_age_max' => 'nullable|integer|between:0,30',
            'preferred_size' => 'nullable|in:Small,Medium,Large,Extra Large',
            'preferred_gender' => 'nullable|in:Male,Female',
            'housing_type' => 'required|in:House,Apartment,Condo',
            'housing_ownership' => 'required|in:Owned,Rented',
            'has_yard' => 'required|boolean',
            'yard_size' => 'nullable|string|max:100',
            'num_adults' => 'required|integer|between:1,20',
            'num_children' => 'required|integer|between:0,20',
            'children_ages' => 'nullable|string|max:255',
            'existing_pets_description' => 'nullable|string|max:2000',
            'previous_pet_experience' => 'nullable|string|max:2000',
            'vet_reference_name' => 'nullable|string|max:150',
            'vet_reference_clinic' => 'nullable|string|max:150',
            'vet_reference_contact' => 'nullable|phone_ph',
            'valid_id_path' => 'required|file|file_max:10240|file_types:jpg,jpeg,png,pdf',
            'agrees_to_policies' => 'required|boolean',
            'agrees_to_home_visit' => 'required|boolean',
            'agrees_to_return_policy' => 'required|boolean',
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors());
        }

        try {
            $application = $this->adoptions->submitPortalApplication(
                (int) $authUser['id'],
                $request->body(),
                (array) $request->file('valid_id_path'),
                $request
            );
        } catch (RuntimeException $exception) {
            return Response::error(409, 'ADOPTION_APPLICATION_BLOCKED', $exception->getMessage());
        }

        return Response::success([
            'application' => $application,
        ], 'Adoption application submitted successfully.');
    }

    public function myApplications(Request $request): Response
    {
        if (($response = $this->ensurePortalEnabled($request)) instanceof Response) {
            return $response;
        }

        $authUser = $request->attribute('auth_user');

        return Response::success(
            $this->adoptions->myApplications((int) $authUser['id']),
            'Adopter applications retrieved successfully.'
        );
    }

    private function ensurePortalEnabled(Request $request): ?Response
    {
        if ((bool) SystemSettings::get('public_portal_enabled', true)) {
            return null;
        }

        return ExceptionHandler::httpErrorResponse(
            $request,
            503,
            'SERVICE_UNAVAILABLE',
            'The public adoption portal is temporarily unavailable.'
        );
    }

    private function renderRegisterPage(
        Request $request,
        array $errors = [],
        array $old = [],
        int $status = 200
    ): Response {
        return $this->renderPortalPage('portal.register', $request, [
            'title' => 'Create Adopter Account',
            'extraCss' => ['/assets/css/portal.css'],
            'extraJs' => ['/assets/js/portal.js'],
            'csrfToken' => CsrfMiddleware::token(),
            'errors' => $errors,
            'old' => $old,
        ], $status);
    }

    private function renderPortalPage(string $view, Request $request, array $data = [], int $status = 200): Response
    {
        return $this->renderPublicView($view, $data + [
            'currentUser' => $this->auth->userFromRequest($request),
        ], $status);
    }
}
