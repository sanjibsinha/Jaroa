<?php

namespace App\Controllers;

use App\Templates\TemplateManager;
use App\View;

class TemplateController
{
    private TemplateManager $templateManager;

    public function __construct(
        TemplateManager $templateManager
    ) {
        $this->templateManager = $templateManager;
    }

    /**
     * Display the template installer screen.
     */
    public function index(): void
    {
        View::render(
            'templates',
            [
                'templates' => $this->templateManager->available(),
                'installedTemplates' => $this->templateManager->installed(),
                'activeTemplate' => $this->templateManager->active(),
            ]
        );
    }

    /**
     * Activate a template.
     */
    public function activate(
        array $params
    ): void {
        $slug = $params['slug'] ?? '';

        if ('' === $slug) {
            throw new \RuntimeException(
                'Template slug is required.'
            );
        }

        $this->templateManager->activate($slug);

        header(
            'Location: /templates'
        );

        exit;
    }
}
