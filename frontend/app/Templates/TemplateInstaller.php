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

        $this->installView(
            $entryPath,
            $slug
        );

        $this->installAssets(
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
     * Install the template view.
     */
    private function installView(
        string $entryPath,
        string $slug
    ): void {
        if (!is_dir($this->viewsPath)) {
            mkdir(
                $this->viewsPath,
                0755,
                true
            );
        }

        $viewPath =
            $this->viewsPath . '/' . $slug . '.php';

        if (!copy(
            $entryPath,
            $viewPath
        )) {
            throw new RuntimeException(
                "Unable to install template view: {$slug}"
            );
        }
    }

    /**
     * Install the complete template asset directory.
     */
    private function installAssets(
        string $templatePath,
        string $slug
    ): void {
        $sourcePath =
            $templatePath . '/assets';

        if (!is_dir($sourcePath)) {
            return;
        }

        $destinationPath =
            $this->assetsPath . '/' . $slug;

        $this->copyDirectory(
            $sourcePath,
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