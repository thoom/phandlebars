<?php
/**
 * HandlebarsServiceProvider Class
 *
 * @author Z. d. Peacock <zdp@thoomtech.com>
 * @copyright (c) 2012 Thoom Technologies LLC
 * @since 9/28/12 9:25 PM
 */

namespace Thoom\Provider;

use Silex\Application;
use Silex\ServiceProviderInterface;

class HandlebarsServiceProvider implements ServiceProviderInterface
{

    /**
     * Registers services on the given app.
     *
     * This method should only be used to configure services and parameters.
     * It should not get services.
     *
     * @param Application $app An Application instance
     */
    public function register(Application $app)
    {
        $app['handlebars'] = $app->share(function () use ($app) {
            return new \Thoom\Renderer\HandlebarsRenderer($app);
        });
    }

    /**
     * Bootstraps the application.
     *
     * This method is called after all services are registers
     * and should be used for "dynamic" configuration (whenever
     * a service must be requested).
     */
    public function boot(Application $app)
    {
        $defaults = array(
            'debug' => false,
            'minify' => false,
            'library' => realpath(__DIR__ . '/../../../assets/handlebars.runtime-1.0.rc.1.js'),
        );

        $options = ($app['handlebars.options']) ? array_merge($defaults, $app['handlebars.options']) : $defaults;

        if (!isset($options['compiled'])) {
            //TODO: Throw an exception
        }

        $app['handlebars.options'] = $options;
    }
}
