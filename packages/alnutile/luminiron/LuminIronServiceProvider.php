<?php
/**
 * Created by PhpStorm.
 * User: alfrednutile
 * Date: 4/14/15
 * Time: 1:58 PM
 */

namespace LuminIron;


use Illuminate\Support\ServiceProvider;

class LuminIronServiceProvider extends ServiceProvider {


    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register()
    {
        $this->app->singleton('LuminIron\Connection', function($app) {
            return new Connection();
        });
    }

    public function boot()
    {

        $this->publishes([
            __DIR__.'/workers' => base_path('workers'),
        ]);
    }
}