Thoom Phandlebars
=================

Summary
-------

Phandlebars allows you to use Handlebars templates in your Silex applications. Rather than reimplementing the Handlebars
library in PHP, this provider uses server-side Javascript (primarily with node.js) to both compile the Handlebars templates
you've created (making the generated JS file also available for your client-side handling) and process templates on the server.

Installation
------------

There are two parts to the installation: the PHP hooks into your Silex application and the required server-side applications.

### Silex Application

There are these options currently:

  1. __debug__: If debug is true, then the compiled file will be overwritten with each request. If false, it will only build the compiled file if it doesn't exist.
  1. __library__: By default, the compiled JS file will use the Handlebars runtime library. If you need to use the full library instead
                  (for instance, if you dynamically compile on the client side), then pass in `HandlebarsServiceProvider::LIBRARY_FULL`.
  1. __minify__: If true, the script will attempt to minify the file using the `runtime.minify` command.
  1. __path.compiled.client__: This is the full file path of where you want the compiled client-only Javascript file to reside. The location should be writable by your web user.
  1. __path.compiled.server__: This is the full file path of where you want the server compiled Javascript file to reside.
  1. __path.templates.client__: The directory where all of the client-only Handlebars templates are stored. Note that all of your templates should end with _.handlebars_.
  1. __path.templates.server__: The directory where all of the server-only Handlebars templates are stored. Note that all of your templates should end with _.handlebars_.
  1. __runtime.minify__: If not set, the script will attempt to minify the client file using `uglifyjs` command.
  1. __runtime.node__: If not set, the script will attempt to run node from using `node` command.

As an example:

    $app->register(new Thoom\Provider\HandlebarsServiceProvider(), array(
        'handlebars.options' => array(
            'debug' => $app['debug'],
            'minify' => true,
            'path.compiled.client' => $app['cache_dir'] . '/js/handlebars-compiled.js',
            'path.compiled.server' => $app['cache_dir'] . '/compiled.js',
            'path.templates.client' => __DIR__ . '/templates/client',
            'path.templates.server' => __DIR__ . '/templates/server',
        )
    ));

### Server

The provider needs a few server cli applications installed in order to render and compile templates.

#### Node.js

Easy to install using the package manager of your choice. For instance, with Ubuntu:

    $ apt-get install nodejs build-essential

This assumes that your node executable is installed and accessible from `node`. If you need to use a different command,
change the `runtime.node` configuration option.

#### Handlebars.js

You'll need to install using NPM.

    $ apt-get install npm
    $ npm install -g handlebars

#### UglifyJS (optional)

If you want to minify your compiled templates, you'll need to install UglifyJS.

    $ npm install -g uglify-js

If you prefer another minifier, note that it just needs to accept data from `STDIN` and outputs to `STDOUT`. To change the
minifier, change the `runtime.minify` configuration option.


To Use
------

### Silex Application

#### Precompile

You may wish to precompile the script. This can be accomplished by calling the renderer from your application using the following command:

    $app['handlebars']->server();

To precompile handlebars for client only scripts:

    $app['handlebars']->client();

#### Server-side rendering

To render a server-side template for "index.handlebars":

    $app->get('/', function (Application $app) {
        return $app['handlebars']->render('index');
    })->bind('homepage');

To add a global variable (like a user array) to be available to your templates:

    $app['handlebars']->addGlobal('user', $userArray);

To pass data to the template at render time, pass in a array as the second argument:

    $app['handlebars']->render('index', array('foo' => 'fez'));

To return an Http header other than 200, pass in a 3rd argument:

    $app['handlebars']->render('not-found', array('foo' => 'fez'), 404);

__Note:__ *Since the Handlebars templates are processed in Javascript using node, you can only pass items that are JSON serializable.*


### Handlebars templates

In addition to the standard [Handlebars.js options](https://handlebarsjs.com), you have a few more options that allow you to extend templates similar to Twig.

To create a master template ('master.handlebars'), use the {{#block}} tag:

    <html>
        <head>
            <title>{{#block "title"}}Master Template{{/block}}</title>
        </head>
        ...


In your child template, you would use the {{#override}} and {{extend}} tags:

    {{#override "title"}}Child Template{{/override}}

    {{extend "master"}}

This would print:

    <html>
        <head>
            <title>Child Template</title>
        </head>
        ...

Notice that unlike Twig templates, the extend tag is found at the bottom of the script. This is required because of how Handlebars parses templates.

#### Additional Tags

##### path

This convenience expression takes all of your Silex named routes and makes them available in your templates.

If your named route "login" has a path of "/login", you'd use:

    {{path "login" }}

If your named route "section" has a path of "/section/{section}", you'd use:

    {{path "section" section="my-section" }}


