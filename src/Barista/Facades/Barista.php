<?php
namespace Barista\Facades;

use Illuminate\Support\Facades\Facade;

class Barista extends Facade {
	protected static function getFacadeAccessor() {
		return 'Barista\BaristaBuilder';
	}
}