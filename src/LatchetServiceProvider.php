<?php namespace Sidney\Latchet;

use Illuminate\Support\ServiceProvider;
use Sidney\Latchet\Console\GenerateCommand;
use Sidney\Latchet\Console\ListenCommand;
use Sidney\Latchet\Generators\Generator;

class LatchetServiceProvider extends ServiceProvider
{

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
        $source = realpath(__DIR__ . '/../config/latchet.php');
        $this->publishes([ $source => config_path('latchet.php') ]);
    }


    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->registerLatchet();
        $this->registerCommands();
    }


    /**
     * Register the application bindings.
     *
     * @return void
     */
    private function registerLatchet()
    {
        $this->app->singleton('latchet', function ($app) {
            return new Latchet($app);
        });
    }


    /**
     * Register the artisan commands.
     *
     * @return void
     */
    private function registerCommands()
    {

        $this->app->singleton('command.latchet.listen', function ($app) {
            return new ListenCommand($app);
        });

        $this->app->singleton('command.latchet.generate', function ($app) {
            $path = app_path('Latchet');

            $generator = new Generator($app['files']);

            return new GenerateCommand($generator, $path);
        });

        $this->commands('command.latchet.listen', 'command.latchet.generate');
    }

}