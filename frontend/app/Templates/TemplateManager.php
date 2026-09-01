<?php

namespace App\Templates;

use RuntimeException;

class TemplateManager
{
    private string $templatesPath;

    private string $activeTemplatePath;

    public function __construct(
        string $templatesPath,
        string $activeTemplatePath
    ) {
        $this->templatesPath = rtrim(
            $templatesPath,
            '/'
        );

        $this->activeTemplatePath = $activeTemplatePath;
    }

    /**
     * Return all available templates.
     */
    public function available(): array
    {
        if (!is_dir($this->templatesPath)) {
            return [];
        }

        $templates = [];

        foreach (scandir($this->templatesPath) as $directory) {

            if (
                '.' === $directory ||
                '..' === $directory
            ) {
                continue;
            }

            $templatePath =
                $this->templatesPath . '/' . $directory;

            if (!is_dir($templatePath)) {
                continue;
            }

            $manifestPath =
                $templatePath . '/template.json';

            if (!is_file($manifestPath)) {
                continue;
            }

            $manifest = json_decode(
                file_get_contents($manifestPath),
                true
            );

            if (!is_array($manifest)) {
                continue;
            }

            $templates[] = $manifest;
        }

        return $templates;
    }

    /**
     * Find a template by slug.
     */
    public function find(
        string $slug
    ): ?array {
        foreach ($this->available() as $template) {
            if (
                isset($template['slug']) &&
                $template['slug'] === $slug
            ) {
                return $template;
            }
        }

        return null;
    }

    /**
     * Return the showcase URL for a template.
     */
    public function showcase(
        string $slug
    ): ?string {
        $template = $this->find($slug);

        if (null === $template) {
            return null;
        }

        return $template['showcase'] ?? null;
    }

    /**
     * Return all installed templates.
     */
    public function installed(): array
    {
        $templates = [];

        foreach ($this->available() as $template) {
            if (
                isset($template['slug']) &&
                $this->isInstalled($template['slug'])
            ) {
                $templates[] = $template;
            }
        }

        return $templates;
    }

    /**
     * Determine whether a template is installed.
     */
    public function isInstalled(
        string $slug
    ): bool {
        foreach ($this->available() as $template) {

            if (
                isset($template['slug']) &&
                $template['slug'] === $slug
            ) {
                return true;
            }
        }

        return false;
    }

    /**
     * Activate an installed template.
     */
    public function activate(
        string $slug
    ): void {
        if (!$this->isInstalled($slug)) {
            throw new RuntimeException(
                "Template is not installed: {$slug}"
            );
        }

        $directory = dirname(
            $this->activeTemplatePath
        );

        if (!is_dir($directory)) {
            mkdir(
                $directory,
                0755,
                true
            );
        }

        if (
            false === file_put_contents(
                $this->activeTemplatePath,
                $slug . PHP_EOL
            )
        ) {
            throw new RuntimeException(
                "Unable to activate template: {$slug}"
            );
        }
    }

    /**
     * Return the currently active template.
     */
    public function active(): ?string
    {
        if (!is_file($this->activeTemplatePath)) {
            return null;
        }

        $slug = trim(
            file_get_contents(
                $this->activeTemplatePath
            )
        );

        return '' === $slug
            ? null
            : $slug;
    }
}
