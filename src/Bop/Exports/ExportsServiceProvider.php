<?php namespace Bop\Exports;

use Illuminate\Support\ServiceProvider;

class ExportsServiceProvider extends ServiceProvider {

	/**
	 * Indicates if loading of the provider is deferred.
	 *
	 * @var bool
	 */
	protected $defer = false;

	/**
	 * Bootstrap the application events.
	 *
	 * @return void
	 */
	public function boot()
	{
		$this->package('bop/exports');

		// Gestion des routes
		include __DIR__.'/../../routes.php';

	}

	/**
	 * Register the service provider.
	 *
	 * @return void
	 */
	public function register()
	{
		$this->app['exports'] = $this->app->share(function($app)
		{
			return new ExportsFactory;
		});
	}

	/**
	 * Get the services provided by the provider.
	 *
	 * @return array
	 */
	public function provides()
	{
		return array('exports');
	}

}
