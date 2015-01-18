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
		// Load the config for now..
		// todo: Implement L5 best practice for loading config
		$config = $this->app['files']->getRequire(__DIR__ .'/config/config.php');
		$this->app['config']->set('warden::config', $config);

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
