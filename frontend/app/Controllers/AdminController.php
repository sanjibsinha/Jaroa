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


    public function allPosts(
        array $parameters = []
    ): void {
        $user = $this->auth->requireRole(
            ['admin', 'editor']
        );

        $posts = $this->posts->paginate(
            1,
            100
        );

        View::render(
            'admin/posts',
            [
                'user' => $user,
                'posts' => $posts['data'] ?? [],
                'total' =>
                    (int) (
                        $posts['meta']['total']
                        ?? 0
                    ),
            ],
            'admin'
        );
    }

    public function addPostForm(
        array $parameters = []
    ): void {
        $user = $this->auth->requireRole(
            ['admin', 'editor']
        );

        View::render(
            'admin/add-post',
            [
                'user' => $user,
                'error' => null,
                'old' => [
                    'title' => '',
                    'slug' => '',
                    'status' => 'published',
                    'content' => '',
                ],
            ],
            'admin'
        );
    }

    public function createPost(
        array $parameters = []
    ): void {
        $user = $this->auth->requireRole(
            ['admin', 'editor']
        );

        $title = trim(
            (string) ($_POST['title'] ?? '')
        );

        $slug = trim(
            (string) ($_POST['slug'] ?? '')
        );

        $status = trim(
            (string) ($_POST['status'] ?? 'published')
        );

        $content = (string) (
            $_POST['content'] ?? ''
        );

        if ($title === '') {
            $this->renderAddPostError(
                $user,
                'Post title is required.',
                compact(
                    'title',
                    'slug',
                    'status',
                    'content'
                )
            );

            return;
        }

        if ($slug === '') {
            $slug = $this->makeSlug($title);
        }

        if ($slug === '') {
            $this->renderAddPostError(
                $user,
                'A valid post slug could not be generated.',
                compact(
                    'title',
                    'slug',
                    'status',
                    'content'
                )
            );

            return;
        }

        if (
            !in_array(
                $status,
                ['draft', 'published'],
                true
            )
        ) {
            $status = 'published';
        }

        $token = $this->auth->token();

        if ($token === null) {
            $this->redirect('/admin/login');
        }

        $payload = [
            'title' => $title,
            'slug' => $slug,
            'content' => $content,
            'status' => $status,
            'user_id' => (int) (
                $user['id'] ?? 0
            ),
        ];

        try {
            $this->api->post(
                '/posts',
                $payload,
                [
                    'Authorization' =>
                        'Bearer ' . $token,
                ]
            );
        } catch (\Throwable) {
            $this->renderAddPostError(
                $user,
                'The post could not be created. Check the post data and try again.',
                compact(
                    'title',
                    'slug',
                    'status',
                    'content'
                )
            );

            return;
        }

        $this->redirect('/admin/posts');
    }

    private function renderAddPostError(
        array $user,
        string $error,
        array $old
    ): void {
        View::render(
            'admin/add-post',
            [
                'user' => $user,
                'error' => $error,
                'old' => [
                    'title' =>
                        (string) (
                            $old['title'] ?? ''
                        ),
                    'slug' =>
                        (string) (
                            $old['slug'] ?? ''
                        ),
                    'status' =>
                        (string) (
                            $old['status']
                            ?? 'published'
                        ),
                    'content' =>
                        (string) (
                            $old['content'] ?? ''
                        ),
                ],
            ],
            'admin'
        );
    }

    private function makeSlug(
        string $title
    ): string {
        $slug = strtolower(
            trim(
                preg_replace(
                    '/[^a-z0-9]+/i',
                    '-',
                    $title
                ) ?? '',
                '-'
            )
        );

        return $slug;
    }


    public function editPostForm(
        array $parameters
    ): void {
        $user = $this->auth->requireRole(
            ['admin', 'editor']
        );

        $id = (int) (
            $parameters['id'] ?? 0
        );

        if ($id <= 0) {
            throw new RuntimeException(
                'Post ID is required.'
            );
        }

        try {
            $post = $this->posts->find($id);
        } catch (\Throwable) {
            http_response_code(404);

            View::render(
                'admin/forbidden',
                [
                    'message' =>
                        'The requested post could not be found.',
                ],
                'admin'
            );

            return;
        }

        if (!is_array($post)) {
            throw new RuntimeException(
                'Post could not be loaded.'
            );
        }

        View::render(
            'admin/edit-post',
            [
                'user' => $user,
                'error' => null,
                'post' => $post,
            ],
            'admin'
        );
    }

    public function updatePost(
        array $parameters
    ): void {
        $user = $this->auth->requireRole(
            ['admin', 'editor']
        );

        $id = (int) (
            $parameters['id'] ?? 0
        );

        if ($id <= 0) {
            throw new RuntimeException(
                'Post ID is required.'
            );
        }

        $title = trim(
            (string) ($_POST['title'] ?? '')
        );

        $slug = trim(
            (string) ($_POST['slug'] ?? '')
        );

        $status = trim(
            (string) ($_POST['status'] ?? 'published')
        );

        $content = (string) (
            $_POST['content'] ?? ''
        );

        if ($title === '') {
            $this->renderEditPostError(
                $user,
                $id,
                'Post title is required.',
                compact(
                    'title',
                    'slug',
                    'status',
                    'content'
                )
            );

            return;
        }

        if ($slug === '') {
            $slug = $this->makeSlug($title);
        }

        if ($slug === '') {
            $this->renderEditPostError(
                $user,
                $id,
                'A valid post slug is required.',
                compact(
                    'title',
                    'slug',
                    'status',
                    'content'
                )
            );

            return;
        }

        if (
            !in_array(
                $status,
                ['draft', 'published'],
                true
            )
        ) {
            $status = 'published';
        }

        $token = $this->auth->token();

        if ($token === null) {
            $this->redirect('/admin/login');
        }

        try {
            $this->api->put(
                '/posts/' . $id,
                [
                    'title' => $title,
                    'slug' => $slug,
                    'content' => $content,
                    'status' => $status,
                ],
                [
                    'Authorization' =>
                        'Bearer ' . $token,
                ]
            );
        } catch (\Throwable) {
            $this->renderEditPostError(
                $user,
                $id,
                'The post could not be updated. Check the post data and try again.',
                compact(
                    'title',
                    'slug',
                    'status',
                    'content'
                )
            );

            return;
        }

        $this->redirect('/admin');
    }

    public function deletePost(
        array $parameters
    ): void {
        $this->auth->requireRole(
            ['admin', 'editor']
        );

        $id = (int) (
            $parameters['id'] ?? 0
        );

        if ($id <= 0) {
            throw new RuntimeException(
                'Post ID is required.'
            );
        }

        $token = $this->auth->token();

        if ($token === null) {
            $this->redirect('/admin/login');
        }

        try {
            $this->api->delete(
                '/posts/' . $id,
                [
                    'Authorization' =>
                        'Bearer ' . $token,
                ]
            );
        } catch (\Throwable $exception) {
            throw new RuntimeException(
                'The post could not be deleted.',
                0,
                $exception
            );
        }

        $this->redirect('/admin');
    }

    private function renderEditPostError(
        array $user,
        int $id,
        string $error,
        array $old
    ): void {
        View::render(
            'admin/edit-post',
            [
                'user' => $user,
                'error' => $error,
                'post' => [
                    'id' => $id,
                    'title' =>
                        (string) (
                            $old['title'] ?? ''
                        ),
                    'slug' =>
                        (string) (
                            $old['slug'] ?? ''
                        ),
                    'status' =>
                        (string) (
                            $old['status']
                            ?? 'published'
                        ),
                    'content' =>
                        (string) (
                            $old['content'] ?? ''
                        ),
                ],
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
