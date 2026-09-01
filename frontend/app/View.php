<?php

namespace App;

use App\Templates\TemplateManager;
use RuntimeException;

class View
{
    private static array $shared = [];

    private static ?TemplateManager $templateManager = null;

    /**
     * Share data with all views.
     */
    public static function share(array $data): void
    {
        self::$shared = array_merge(
            self::$shared,
            $data
        );
    }

    /**
     * Set the template manager.
     */
    public static function setTemplateManager(
        TemplateManager $templateManager
    ): void {
        self::$templateManager = $templateManager;
    }

    /**
     * Render a normal application view.
     */
    public static function render(
        string $view,
        array $data = []
    ): void {
        self::renderFile(
            self::viewPath($view),
            $data
        );
    }

    /**
     * Render the currently active template.
     */
    public static function renderTemplate(
        array $data = []
    ): void {
        if (null === self::$templateManager) {
            throw new RuntimeException(
                'Template manager has not been configured.'
            );
        }

        $template = self::$templateManager->active();

        if (null === $template) {
            throw new RuntimeException(
                'No active template.'
            );
        }

        self::renderFile(
            self::viewPath($template),
            $data
        );
    }

    /**
     * Render an installed template by its slug.
     */
    public static function renderTemplateBySlug(
        string $slug,
        array $data = []
    ): void {
        if (null === self::$templateManager) {
            throw new RuntimeException(
                'Template manager has not been configured.'
            );
        }

        if (!self::$templateManager->isInstalled($slug)) {
            throw new RuntimeException(
                "Template is not installed: {$slug}"
            );
        }

        self::renderFile(
            self::viewPath($slug),
            $data
        );
    }

    /**
     * Render a view file through the application layout.
     */
    private static function renderFile(
        string $viewPath,
        array $data
    ): void {
        extract(
            array_merge(
                self::$shared,
                $data
            ),
            EXTR_SKIP
        );

        ob_start();

        require $viewPath;

        $content = ob_get_clean();

        $layoutPath =
            __DIR__ . '/../views/layouts/app.php';

        if (!is_file($layoutPath)) {
            throw new RuntimeException(
                'Application layout not found.'
            );
        }

        require $layoutPath;
    }

    /**
     * Resolve a normal view path.
     */
    private static function viewPath(
        string $view
    ): string {
        $viewPath =
            __DIR__ .
            '/../views/' .
            $view .
            '.php';

        if (!is_file($viewPath)) {
            throw new RuntimeException(
                "View not found: {$view}"
            );
        }

        return $viewPath;
    }
}
