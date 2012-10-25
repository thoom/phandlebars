<?php
/**
 * HandlebarsRenderer Class
 *
 * @author Z. d. Peacock <zdp@thoomtech.com>
 * @copyright (c) 2012 Thoom Technologies LLC
 * @since 9/28/12 9:40 PM
 */

namespace Thoom\Renderer;


use Symfony\Component\HttpFoundation\Response;

class HandlebarsRenderer
{
    protected $app;
    protected $globals;

    public function __construct($app)
    {
        $this->app = $app;
        $this->globals = array();
    }

    public function render($template, $params = array(), $code = 200)
    {
        if (is_numeric($params))
            $code = $params;

        if (!is_array($params))
            $params = array();

        $params = array_merge($this->globals, $params);
        $descriptorspec = array(
            0 => array('pipe', 'r'),
            1 => array('pipe', 'w'),
            2 => array('pipe', 'w'),
        );

        $process = proc_open('node', $descriptorspec, $pipes);

        $json = json_encode($params);
        $combined = $this->compile() . "\n\nvar template = Handlebars.templates['$template']($json);\nconsole.log(template);";

        fwrite($pipes[0], $combined);
        fclose($pipes[0]);

        $results = stream_get_contents($pipes[1]);
        fclose($pipes[1]);

        $errors = stream_get_contents($pipes[2]);
        fclose($pipes[2]);

        proc_close($process);

        if ($errors) {
            throw new Exception("Errors rendering Handlebars template: " . $errors);
        }

        return new Response($results, $code);
    }

    public function addGlobal($key, $val)
    {
        $this->globals[$key] = $val;
    }

    public function compile()
    {
        if (!$this->app['handlebars.options']['debug'] && is_file($this->app['handlebars.options']['compiled']))
            return file_get_contents($this->app['handlebars.options']['compiled']);

        $library = str_replace('this.Handlebars', 'Handlebars', file_get_contents($this->app['handlebars.options']['library.path']));

        $templatesCmd = 'handlebars ' . $this->app['handlebars.options']['path'];
        $minify = $this->app['handlebars.options']['minify'];
        if ($minify)
            $templatesCmd .= ' -m';

        $handle = popen($templatesCmd, 'r');
        $templates = stream_get_contents($handle);
        pclose($handle);

        $routes = $this->app['routes']->all();
        $requirements = array();

        foreach ($routes as $key => $route) {
            if (stripos($key, '_') === 0)
                continue;

            $requirements[$key] = $route->getPattern();
        }

        $paths = json_encode($requirements);

        //If not in debug mode, serve this file from cache
        $combined = <<<JS
$library

$templates

Handlebars.loadPartial = function loadPartial(name) {
    var partial = Handlebars.partials[name];
    if (typeof partial === "string") {
        partial = Handlebars.compile(partial);
        Handlebars.partials[name] = partial;
    }
    return partial;
};

var renderInherited = function renderInherited(context, name, saved, child, parent) {
    Handlebars.registerPartial(name, parent);
    var out = child(context);
    Handlebars.registerPartial(name, saved);
    return out;
};

Handlebars.registerHelper("override", function override(name, options) {

    /*
     * Would be nice to extend Handlebars so that the blocks dictionary would reset at every top-level instantiation,
     * or better yet, pass it around in the options (instead of using a module-level variable). To avoid such invasion,
     * though, we check to initialize before every use, and clear after all uses finished.
     */
    var blocks = Handlebars.blocks = Handlebars.blocks || Object.create(null);

    var override = blocks[name];
    var parent = options.fn;

    if (override) {
        var wrapper = function wrapper(context) {
            var grandparent = Handlebars.loadPartial(name);
            var parentWrapper = function parentWrapper(subcontext) {
                return renderInherited(/*context=*/subcontext, name,
                                       /*saved=*/parentWrapper,
                                       /*child=*/parent,
                                       /*parent=*/grandparent);
            };
            return renderInherited(context, name,
                                   /*saved=*/grandparent,
                                   /*child=*/override,
                                   /*parent=*/parentWrapper);
        };
    } else {
        var wrapper = parent;
    }

    blocks[name] = wrapper;
});


Handlebars.registerHelper("block", function block(name, options) {
    var blocks = Handlebars.blocks = Handlebars.blocks || Object.create(null);

    var override = blocks[name];
    if (override) {
        /*
         * We let templates include parent blocks with regular partials---e.g., `{{> parent}}`---but we cannot "store"
         * the blocks as partials - we have to discriminate between blocks and partials so that we can clear the former
         * but not the latter at the end of every top-level instantiation.
         */
        var out = renderInherited(/*context=*/this, name,
                                  /*saved=*/undefined,
                                  /*child=*/override,
                                  /*parent=*/options.fn);
    } else {
        var out = options.fn(this);
    }

    return out;
});

Handlebars.registerHelper("extend", function extend(name) {

    var base = Handlebars.loadPartial(name);
    if (!base) {
       var compiled = Handlebars.templates[name];
       Handlebars.registerPartial(name, compiled);

       base = Handlebars.loadPartial(name);
    }
    var out = base(this);
    delete Handlebars.blocks;
    return new Handlebars.SafeString(out);

});

Handlebars.registerHelper('path', function (key, options) {
    var paths = $paths;
    var path = '';
    if (key in paths) {
        path = paths[key];
    }

    for (var prop in options.hash) {
        path = path.replace('{' + prop + '}', options.hash[prop]);
    }

    return path;
});
JS;

        $dir = dirname($this->app['handlebars.options']['compiled']);
        if (!file_exists($dir))
            mkdir($dir, 0777, true);

        if ($minify) {
            $descriptorspec = array(
                0 => array('pipe', 'r'),
                1 => array('pipe', 'w'),
                2 => array('pipe', 'w'),
            );

            $process = proc_open('uglifyjs', $descriptorspec, $pipes);

            fwrite($pipes[0], $combined);
            fclose($pipes[0]);

            $results = stream_get_contents($pipes[1]);
            fclose($pipes[1]);

            $errors = stream_get_contents($pipes[2]);
            fclose($pipes[2]);

            proc_close($process);

            if ($errors)
                throw new Exception("Errors minifying Handlebars template: " . $errors);

            $combined = $results;
        }

        file_put_contents($this->app['handlebars.options']['compiled'], $combined);

        return $combined;
    }
}
