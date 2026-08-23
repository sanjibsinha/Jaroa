<?php

namespace App;

use RuntimeException;

class View
{
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
            $data,
            EXTR_SKIP
        );

        require $viewPath;
    }
}