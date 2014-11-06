<?php namespace Devonzara\Warden;

use Illuminate\Support\Facades\Facade;

class WardenFacade extends Facade {

	/**
	 * Get the registered name of the component.
	 *
	 * @return string
	 */
	protected static function getFacadeAccessor()
	{
		return 'warden';
	}

}
