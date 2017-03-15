<?php
namespace GGuney\Barista\Facades;

use Illuminate\Support\Facades\Facade;

class Barista extends Facade {

	/**
	 * Facade for usage from anywhere in your app.
	 * 
	 * @return class
	 */
	protected static function getFacadeAccessor() {
		return 'GGuney\Barista\BaristaBuilder';
	}
	
}