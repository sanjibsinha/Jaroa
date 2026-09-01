<?php

namespace App\Controllers;


use App\View;

class ProfileController
{
    /**
     * Display the profile starter template.
     */
    public function index(): void
    {
        View::render(
            'profile',
            [
                'profile' => [
                    'name' => 'Sanjib Deb Sinha',
                    'role' => 'Writer · Developer · AI Hobbyist',
                    'bio' => 'Exploring ideas, technology, writing and the spaces where they meet.',
                ],
            ],
            'app'
        );
    }
}
