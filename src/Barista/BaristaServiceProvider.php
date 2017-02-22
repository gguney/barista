<?php
namespace Barista;
use Illuminate\Support\ServiceProvider;
class BaristaServiceProvider extends ServiceProvider
{
    /**
     * Register the service provider.
     *
     * @return void
     */
    public function register(){
        $this->mergeConfigFrom(
            __DIR__.'/Publish/barista.php', 'barista'
        );
    }
    public function boot()
    {
        $this->publishes([
            __DIR__.'/Publish/barista.php' => config_path('barista.php'),
        ]);
    }
}
