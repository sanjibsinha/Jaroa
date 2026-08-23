<?php

namespace FullStack\App;

use FullStack\App\Api\Registrar;
use FullStack\App\Http\Controllers\Controller;

class Bootstrap {

	/**
	 * Bootstrap the application.
	 */
	public static function boot(): void {

		add_action(
			'rest_api_init',
			[ Registrar::class, 'register' ]
		);
	}
}