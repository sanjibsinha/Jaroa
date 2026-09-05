<?php

namespace App\Controllers;

use App\Admin\AdminAuth;
use App\Api\ApiClient;
use App\Services\PostService;
use App\Templates\TemplateInstaller;
use App\Templates\TemplateManager;
use App\View;
use RuntimeException;

final class AdminController
{
    public function __construct(
        private readonly AdminAuth $auth,
        private readonly PostService $posts,
        private readonly TemplateManager $templates,
        private readonly TemplateInstaller $templateInstaller,
        private readonly ApiClient $api
    ) {
    }

    public function loginForm(
        array $parameters = []
    ): void {
        if ($this->auth->isAuthenticated()) {
            $this->redirect('/admin');
        }

        View::render(
            'admin/login',
            [
                'error' => null,
                'email' => '',
            ],
            'admin'
        );
    }

    public function login(
        array $parameters = []
    ): void {
        $email = trim(
            (string) ($_POST['email'] ?? '')
        );

        $password = (string) (
            $_POST['password'] ?? ''
        );

        if (
            $email === '' ||
            $password === ''
        ) {
            View::render(
                'admin/login',
                [
                    'error' =>
                        'Email and password are required.',
                    'email' => $email,
                ],
                'admin'
            );

            return;
        }

        try {
            $user = $this->auth->login(
                $email,
                $password
            );

            $role = $user['role'] ?? '';

            if (
                !in_array(
                    $role,
                    ['admin', 'editor'],
                    true
                )
            ) {
                $this->auth->logout();

                View::render(
                    'admin/login',
                    [
                        'error' =>
                            'This account does not have admin access.',
                        'email' => $email,
                    ],
                    'admin'
                );

                return;
            }
        } catch (\Throwable) {
            View::render(
                'admin/login',
                [
                    'error' =>
                        'Unable to sign in. Check your credentials.',
                    'email' => $email,
                ],
                'admin'
            );

            return;
        }

        $this->redirect('/admin');
    }

    public function dashboard(
        array $parameters = []
    ): void {
        try {
            $user = $this->auth->requireRole(
                ['admin', 'editor']
            );
        } catch (RuntimeException $exception) {
            http_response_code(403);

            View::render(
                'admin/forbidden',
                [
                    'message' => $exception->getMessage(),
                ],
                'admin'
            );

            return;
        }

        $posts = $this->posts->paginate(
            1,
            5
        );

        $status = null;

        try {
            $statusResponse =
                $this->api->get('/status');

            $status =
                $statusResponse['status']
                ?? null;
        } catch (\Throwable) {
            $status = null;
        }

        View::render(
            'admin/dashboard',
            [
                'user' => $user,
                'postCount' =>
                    (int) (
                        $posts['meta']['total']
                        ?? 0
                    ),
                'recentPosts' =>
                    $posts['data']
                    ?? [],
                'engineOnline' =>
                    $status === 'ok',
                'activeTemplate' =>
                    $this->templates->active(),
                'templates' =>
                    $this->templates->available(),
            ],
            'admin'
        );
    }

    public function templates(
        array $parameters = []
    ): void {
        $user = $this->auth->requireRole(
            ['admin', 'editor']
        );

        View::render(
            'admin/templates',
            [
                'user' => $user,
                'templates' =>
                    $this->templates->available(),
                'installedTemplates' =>
                    $this->templates->installed(),
                'activeTemplate' =>
                    $this->templates->active(),
            ],
            'admin'
        );
    }

    public function installTemplate(
        array $parameters
    ): void {
        $this->auth->requireRole(
            ['admin', 'editor']
        );

        $slug = $parameters['slug'] ?? '';

        if (!is_string($slug) || $slug === '') {
            throw new RuntimeException(
                'Template slug is required.'
            );
        }

        $this->templateInstaller->install(
            $slug
        );

        $this->redirect('/admin/templates');
    }

    public function activateTemplate(
        array $parameters
    ): void {
        $this->auth->requireRole(
            ['admin', 'editor']
        );

        $slug = $parameters['slug'] ?? '';

        if (!is_string($slug) || $slug === '') {
            throw new RuntimeException(
                'Template slug is required.'
            );
        }

        $this->templates->activate($slug);

        $this->redirect('/admin/templates');
    }

    public function logout(
        array $parameters = []
    ): void {
        $this->auth->logout();

        $this->redirect('/admin/login');
    }

    private function redirect(
        string $path
    ): never {
        header('Location: ' . $path);
        exit;
    }
}
