<?php

namespace App;

use RuntimeException;

class View
{
    private static array $shared = [];

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
     * Render a view.
     */
    public static function render(
        string $view,
        array $data = [],
        ?string $layout = null
    ): void {
        $viewPath = __DIR__ . '/../views/' . $view . '.php';

        if (!is_file($viewPath)) {
            throw new RuntimeException(
                "View not found: {$view}"
            );
        }

        $viewData = array_merge(
            self::$shared,
            $data
        );

        extract(
            $viewData,
            EXTR_SKIP
        );

        if (null === $layout) {
            require $viewPath;

            return;
        }

        ob_start();

        require $viewPath;

        $content = ob_get_clean();

        $layoutPath = __DIR__ . '/../views/layouts/' . $layout . '.php';

        if (!is_file($layoutPath)) {
            throw new RuntimeException(
                "Layout not found: {$layout}"
            );
        }

        extract(
            $viewData,
            EXTR_SKIP
        );

        require $layoutPath;
    }
}
