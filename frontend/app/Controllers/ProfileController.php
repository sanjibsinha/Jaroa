<?php

namespace App\Controllers;

use App\View;

class ProfileController
{
    /**
     * Return the Profile template data.
     */
    public static function data(): array
    {
        return [
            'name' => 'Alex Morrow',
            'role' => 'Writer · Developer · Creative Technologist',
            'bio' => 'I build thoughtful digital experiences where language, technology and human curiosity meet.',
            'location' => 'London, United Kingdom',
            'availability' => 'Available for selected projects',
            'email' => 'hello@alexmorrow.example',
            'hero_image' => '/themes/profile/assets/images/hero.svg',
            'statement' => 'I am interested in the space between things. Between language and machines. Between structure and intuition. Between what we know and what we are still trying to understand.',
            'expertise' => [
                [
                    'number' => '01',
                    'title' => 'Writing',
                    'text' => 'Essays, long-form writing, storytelling and editorial work.',
                ],
                [
                    'number' => '02',
                    'title' => 'Software',
                    'text' => 'PHP, web applications, architecture and thoughtful interfaces.',
                ],
                [
                    'number' => '03',
                    'title' => 'Artificial Intelligence',
                    'text' => 'Experiments with generative systems, language and emerging tools.',
                ],
                [
                    'number' => '04',
                    'title' => 'Design',
                    'text' => 'Visual systems, digital products and experiences with character.',
                ],
            ],
            'projects' => [
                [
                    'number' => '01',
                    'title' => 'The Atlas of Small Things',
                    'category' => 'Digital Archive',
                    'description' => 'An experimental collection of overlooked objects, places and observations gathered from everyday life.',
                    'image' => '/themes/profile/assets/images/project-01.svg',
                ],
                [
                    'number' => '02',
                    'title' => 'Night Signal',
                    'category' => 'Experimental Publication',
                    'description' => 'A quiet digital publication exploring cities after dark through essays, photography and fragments.',
                    'image' => '/themes/profile/assets/images/project-02.svg',
                ],
                [
                    'number' => '03',
                    'title' => 'Common Ground',
                    'category' => 'Creative Technology',
                    'description' => 'A small interactive project about how people, machines and shared spaces communicate.',
                    'image' => '/themes/profile/assets/images/project-03.svg',
                ],
            ],
            'articles' => [
                [
                    'date' => '14 AUG 2026',
                    'type' => 'Essay · 8 min read',
                    'title' => 'The strange usefulness of unfinished ideas',
                    'excerpt' => 'Some ideas become valuable precisely because they resist becoming complete.',
                ],
                [
                    'date' => '29 JUL 2026',
                    'type' => 'Technology · 6 min read',
                    'title' => 'Why software architecture is also a form of writing',
                    'excerpt' => 'Every system tells a story about what its maker believes should belong together.',
                ],
                [
                    'date' => '11 JUN 2026',
                    'type' => 'Reflection · 11 min read',
                    'title' => 'Machines, memory and the things we choose to keep',
                    'excerpt' => 'Digital memory looks infinite until we ask what deserves to survive.',
                ],
            ],
        ];
    }

    /**
     * Display the profile starter template.
     */
    public function index(): void
    {
        View::renderTemplate(
            [
                'profile' => self::data(),
            ]
        );
    }
}
