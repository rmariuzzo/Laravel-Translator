<?php namespace Mariuzzo\Translator;

use Illuminate\Support\ServiceProvider;

/**
 * The TranslatorServiceProvider class.
 *
 * @author rmariuzzo <rubens@mariuzzo.com>
 */
class TranslatorServiceProvider extends ServiceProvider {

	/**
	 * Indicates if loading of the provider is deferred.
	 *
	 * @var bool
	 */
	protected $defer = true;

	/**
	 * Bootstrap the application events.
	 *
	 * @return void
	 */
	public function boot()
	{
		$this->package('mariuzzo/laravel-translator');
	}

	/**
	 * Register the service provider.
	 *
	 * @return void
	 */
	public function register()
	{
		$this->app['translator.command.start'] = $this->app->share(function ($app) {
            return new Commands\TranslatorStartCommand($app['files'], $app['config']);
        });
        $this->commands('translator.command.start');
	}

	/**
	 * Get the services provided by the provider.
	 *
	 * @return array
	 */
	public function provides()
	{
		return array('translator.command.start');
	}

}
