<?php

namespace Rareloop\Lumberjack\DebugBar;

use Rareloop\Lumberjack\DebugBar\DebugBar;
use Rareloop\Lumberjack\DebugBar\Responses\CssResponse;
use Rareloop\Lumberjack\DebugBar\Responses\JavaScriptResponse;
use Rareloop\Lumberjack\Facades\Config;
use Rareloop\Lumberjack\Providers\ServiceProvider;
use Rareloop\Router\Router;
use Zend\Diactoros\Response;

class DebugBarServiceProvider extends ServiceProvider
{
    public function register()
    {
        if (Config::get('app.debug')) {
            $debugbar = $this->app->make(DebugBar::class);

            $this->app->bind('debugbar', $debugbar);
            $this->app->bind('debugbar.messages', $debugbar['messages']);
        }
    }

    public function boot(Router $router)
    {
        if ($this->app->has('debugbar')) {
            // Attempt to add the debug bar to the footer
            add_action('wp_footer', [$this, 'echoDebugBar']);

            // Check to make sure that render has been called. Typical reasons it may not:
            // - WP Class name issue => whitescreen
            add_action('wp_before_admin_bar_render', [$this, 'echoDebugBar']);

            $router->group('debugbar', function ($group) {
                $debugbar = $this->app->get('debugbar');

                $group->get('debugbar.js', function () use ($debugbar) {
                    return new JavaScriptResponse($debugbar->getJavascriptRenderer()->getJsAssetsDump());
                })->name('debugbar.js');

                $group->get('debugbar.css', function () use ($debugbar) {
                    return new CssResponse($debugbar->getJavascriptRenderer()->getCssAssetsDump());
                })->name('debugbar.css');
            });
        }
    }

    public function echoDebugBar()
    {
        $debugbar = $this->app->get('debugbar');

        if ($debugbar->hasBeenRendered()) {
            return;
        }

        echo $debugbar->render();
    }
}
