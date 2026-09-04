<?php

namespace App\Templates;

use RuntimeException;

class TemplateInstaller
{
    private string $templatesPath;

    private string $themesPath;

    public function __construct(
        string $templatesPath,
        string $themesPath
    ) {
        $this->templatesPath = rtrim(
            $templatesPath,
            '/'
        );

        $this->themesPath = rtrim(
            $themesPath,
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

        $manifest = $this->loadManifest(
            $templatePath,
            $slug
        );

        $entryPath = $templatePath . '/' . $manifest['entry'];

        if (!is_file($entryPath)) {
            throw new RuntimeException(
                "Template entry not found: {$slug}"
            );
        }

        $this->installTheme(
            $templatePath,
            $slug
        );
    }

    /**
     * Load and validate a template manifest.
     */
    private function loadManifest(
        string $templatePath,
        string $slug
    ): array {
        $manifestPath = $templatePath . '/template.json';

        if (!is_file($manifestPath)) {
            throw new RuntimeException(
                "Template manifest not found: {$slug}"
            );
        }

        $contents = file_get_contents(
            $manifestPath
        );

        if (false === $contents) {
            throw new RuntimeException(
                "Unable to read template manifest: {$slug}"
            );
        }

        $manifest = json_decode(
            $contents,
            true
        );

        if (!is_array($manifest)) {
            throw new RuntimeException(
                "Invalid template manifest: {$slug}"
            );
        }

        if (empty($manifest['slug'])) {
            throw new RuntimeException(
                "Template manifest is missing slug: {$slug}"
            );
        }

        if ($manifest['slug'] !== $slug) {
            throw new RuntimeException(
                "Template slug mismatch: {$slug}"
            );
        }

        if (empty($manifest['entry'])) {
            throw new RuntimeException(
                "Template manifest is missing entry: {$slug}"
            );
        }

        return $manifest;
    }

    /**
     * Install the complete template into the editable theme directory.
     */
    private function installTheme(
        string $templatePath,
        string $slug
    ): void {
        $destinationPath =
            $this->themesPath . '/' . $slug;

        if (is_dir($destinationPath)) {
            return;
        }

        $this->copyDirectory(
            $templatePath,
            $destinationPath
        );
    }

    /**
     * Recursively copy a directory.
     */
    private function copyDirectory(
        string $source,
        string $destination
    ): void {
        if (!is_dir($destination)) {
            mkdir(
                $destination,
                0755,
                true
            );
        }

        $items = scandir($source);

        if (false === $items) {
            throw new RuntimeException(
                "Unable to read template assets."
            );
        }

        foreach ($items as $item) {

            if ('.' === $item || '..' === $item) {
                continue;
            }

            $sourcePath =
                $source . '/' . $item;

            $destinationPath =
                $destination . '/' . $item;

            if (is_dir($sourcePath)) {

                $this->copyDirectory(
                    $sourcePath,
                    $destinationPath
                );

                continue;
            }

            if (!copy(
                $sourcePath,
                $destinationPath
            )) {
                throw new RuntimeException(
                    "Unable to install template asset: {$item}"
                );
            }
        }
    }
}