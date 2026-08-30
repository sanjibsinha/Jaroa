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
        array $data = []
    ): void {
        $viewPath = __DIR__ . '/../views/' . $view . '.php';

        if (!is_file($viewPath)) {
            throw new RuntimeException(
                "View not found: {$view}"
            );
        }

        extract(
            array_merge(
                self::$shared,
                $data
            ),
            EXTR_SKIP
        );

        require $viewPath;
    }
}
