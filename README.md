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

There are only 4 options currently:

  1. __compiled__: This is the full file path of where you want the compiled Javascript file to reside. The location should be writable by your web user.
  2. __debug__: If debug is true, then the compiled file will be overwritten with each request. If false, it will only build the compiled file if it doesn't exist.
  3. __minify__: If true, the script will attempt to minify the file using _uglifyjs_.
  4. __path__: The directory where all of the Handlebars templates are stored. Note that all of your templates should end with _.handlebars_.

As an example:

    $app->register(new Thoom\Provider\HandlebarsServiceProvider(), array(
        'handlebars.options' => array(
            'compiled' => $app['cache_dir'] . '/js/handlebars-compiled.js',
            'debug' => $app['debug'],
            'minify' => true,
            'path' => __DIR__ . '/templates',
        )
    ));

### Server

The template needs a few server cli applications installed in order to work properly.

#### Node.js

Easy to install using the package manager of your choice. For instance, with ubuntu:

    $ apt-get install nodejs build-essential

#### Handlebars.js

You'll need to install using NPM.

    $ apt-get install npm
    $ npm install -g handlebars

#### UglifyJS (optional)

If you want to minify your compiled templates, you'll need to install UglifyJS.

    $ npm install -g uglify-js

To Use
------



### Silex Application

#### Precompile

You may wish to precompile the script. This can be accomplished by calling the renderer from your application using the following command:

    $app['handlebars']->compile();

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


