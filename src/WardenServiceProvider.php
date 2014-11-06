<?php namespace Devonzara\Warden;

use Illuminate\Support\ServiceProvider;

class WardenServiceProvider extends ServiceProvider {

	/**
	 * Indicates if loading of the provider is deferred.
	 *
	 * @var bool
	 */
	protected $defer = false;

	/**
	 * Register the service provider.
	 *
	 * @return void
	 */
	public function register()
	{
		$this->package('devonzara/warden', 'warden', __DIR__);

		$this->app->bind('warden', 'Devonzara\Warden\Warden');
	}

	/**
	 * Get the services provided by the provider.
	 *
	 * @return array
	 */
	public function provides()
	{
		return ['warden'];
	}

}
