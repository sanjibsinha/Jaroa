<?php

namespace App\Controllers;

use App\Templates\TemplateInstaller;
use App\Templates\TemplateManager;
use App\View;

class TemplateController
{
    private TemplateManager $templateManager;

    private TemplateInstaller $templateInstaller;

    public function __construct(
        TemplateManager $templateManager,
        TemplateInstaller $templateInstaller
    ) {
        $this->templateManager = $templateManager;
        $this->templateInstaller = $templateInstaller;
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
     * Install a template.
     */
    public function install(
        array $params
    ): void {
        $slug = $params['slug'] ?? '';

        if ('' === $slug) {
            throw new \RuntimeException(
                'Template slug is required.'
            );
        }

        $this->templateInstaller->install(
            $slug
        );

        header(
            'Location: /templates'
        );

        exit;
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
