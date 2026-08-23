<?php

namespace FullStack\App\Api;
use FullStack\Domain\Repository\Repository;

class Registrar {

	/**
	 * Register all application API modules.
	 */
	public static function register(): void {

		$apis = [
			Rest::class,
			Status::class,
			Posts::class,
			Categories::class,
		];

		foreach ( $apis as $api ) {

			if (
				class_exists( $api ) &&
				method_exists( $api, 'register' )
			) {
				$api::register();
			}
		}

		Posts::register();
        Categories::register();
        Apps::register(); // <--- Add your new Apps endpoint registration here
	}
	/**
     * Register all REST API routes.
     */
    
}




