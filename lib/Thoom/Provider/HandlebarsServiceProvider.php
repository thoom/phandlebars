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

use Thoom\Renderer\Exception;

class HandlebarsServiceProvider implements ServiceProviderInterface
{

    const LIBRARY_FULL = 'full';
    const LIBRARY_RUNTIME = 'runtime';

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
            'library' => self::LIBRARY_RUNTIME,
            'library.runtime' => realpath(__DIR__ . '/../../../assets/handlebars.runtime-1.0.rc.1.js'),
            'library.full' => realpath(__DIR__ . '/../../../assets/handlebars-1.0.rc.1.js'),
        );

        $options = ($app['handlebars.options']) ? array_merge($defaults, $app['handlebars.options']) : $defaults;

        if (in_array($app['handlebars.options']['library'], array(self::LIBRARY_FULL, self::LIBRARY_RUNTIME)))
            $app['handlebars.options']['library.path'] = $app['handlebars.options']['library.' . $app['handlebars.options']['library']];
        else if (is_file($app['handlebars.options']['library']))
            $app['handlebars.options']['library.path'] = $app['handlebars.options']['library'];
        else
            throw new Exception("Handlebars library not found");

        if (!isset($options['compiled']))
            throw new Exception("Handlebars compiled path not set");

        $app['handlebars.options'] = $options;
    }
}
