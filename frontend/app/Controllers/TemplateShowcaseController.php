<?php

namespace App\Controllers;

use App\Exceptions\NotFoundException;
use App\Templates\TemplateManager;
use App\View;

class TemplateShowcaseController
{
    private TemplateManager $templateManager;

    public function __construct(
        TemplateManager $templateManager
    ) {
        $this->templateManager = $templateManager;
    }

    /**
     * Display a template showcase page.
     */
    public function show(array $params): void
    {
        $slug = $params['slug'] ?? '';

        if ('' === $slug) {
            throw new NotFoundException(
                'Template slug is required.'
            );
        }

        $template = $this->templateManager->find(
            $slug
        );

        if (
            null === $template ||
            !$this->templateManager->isInstalled($slug)
        ) {
            throw new NotFoundException(
                "Template not found: {$slug}"
            );
        }

        View::renderTemplateBySlug(
            $slug,
            [
                'template' => $template,
                'profile' => ProfileController::data(),
            ]
        );
    }
}
