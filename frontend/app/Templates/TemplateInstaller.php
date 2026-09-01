<?php

namespace App\Templates;

use RuntimeException;

class TemplateInstaller
{
    private string $templatesPath;

    private string $viewsPath;

    private string $assetsPath;

    public function __construct(
        string $templatesPath,
        string $viewsPath,
        string $assetsPath
    ) {
        $this->templatesPath = rtrim(
            $templatesPath,
            '/'
        );

        $this->viewsPath = rtrim(
            $viewsPath,
            '/'
        );

        $this->assetsPath = rtrim(
            $assetsPath,
            '/'
        );
    }

    /**
     * Install a template by its slug.
     */
    public function install(string $slug): void
    {
        $templatePath = $this->templatesPath . '/' . $slug;

        if (!is_dir($templatePath)) {
            throw new RuntimeException(
                "Template not found: {$slug}"
            );
        }

        $manifestPath = $templatePath . '/template.json';

        if (!is_file($manifestPath)) {
            throw new RuntimeException(
                "Template manifest not found: {$slug}"
            );
        }

        $manifest = json_decode(
            file_get_contents($manifestPath),
            true
        );

        if (
            !is_array($manifest) ||
            empty($manifest['slug']) ||
            empty($manifest['entry'])
        ) {
            throw new RuntimeException(
                "Invalid template manifest: {$slug}"
            );
        }

        if ($manifest['slug'] !== $slug) {
            throw new RuntimeException(
                "Template slug mismatch: {$slug}"
            );
        }

        $entryPath = $templatePath . '/' . $manifest['entry'];

        if (!is_file($entryPath)) {
            throw new RuntimeException(
                "Template entry not found: {$slug}"
            );
        }

        $viewPath = $this->viewsPath . '/' . $slug . '.php';

        if (!is_dir($this->viewsPath)) {
            mkdir(
                $this->viewsPath,
                0755,
                true
            );
        }

        if (!copy($entryPath, $viewPath)) {
            throw new RuntimeException(
                "Unable to install template view: {$slug}"
            );
        }

        if (!empty($manifest['stylesheet'])) {
            $stylesheetPath =
                $templatePath . '/' . $manifest['stylesheet'];

            if (!is_file($stylesheetPath)) {
                throw new RuntimeException(
                    "Template stylesheet not found: {$slug}"
                );
            }

            $relativeStylesheet = $manifest['stylesheet'];

            $targetStylesheet =
                $this->assetsPath . '/' . $slug . '/' .
                ltrim(
                    preg_replace(
                        '#^assets/#',
                        '',
                        $relativeStylesheet
                    ),
                    '/'
                );

            $targetDirectory = dirname(
                $targetStylesheet
            );

            if (!is_dir($targetDirectory)) {
                mkdir(
                    $targetDirectory,
                    0755,
                    true
                );
            }

            if (!copy(
                $stylesheetPath,
                $targetStylesheet
            )) {
                throw new RuntimeException(
                    "Unable to install template stylesheet: {$slug}"
                );
            }
        }
    }
}