# framework
_Requires PHP 8.1.0 or later_

Ultra-minimal PHP, erm, framework.

**Oh no, not another one...**

Fair point - but this one is _really_ small and super useful, promise.

**How small?**

Just under 10 kB. You'll hardly know it's there.

**Go on then. How do I get started?**

Let's start at the very beginning; a very good place to start. From your project root folder:

- Install it with [Composer](https://getcomposer.org/):

  ```sh
  composer require jhuddle/framework
  ```

- Then, by way of example, create the following files:

  `templates/layout.php`
  ```html
  <!DOCTYPE html>
  <html>
    <head>
      <meta charset="utf-8">
      <meta name="viewport" content="width=device-width, initial-scale=1">
      <title><?= $title ?></title>
    </head>
    <body>
      <header>
        <h1><?= $title ?></h1>
      </header>
      <main>
        <?= $content ?>
      </main>
    </body>
  </html>
  ```

  `templates/partials/list.php`
  ```php
  <ul>
    <?php foreach($items as $item): ?>
      <li><?= $item ?></li>
    <?php endforeach; ?>
  </ul>
  ```

  `public/index.php`
  ```php
  <?php

  // Adjust paths to match your project structure:

  require_once __DIR__ . '/../vendor/autoload.php';
  require_once __DIR__ . '/../app/app.php';
  ```

  `app/app.php`
  ```php
  <?php

  // Instantiate app and define it as a constant:

  define('app', new \Framework\App([
    'view' => dir('../templates'),
  ]));

  // Declare routes:

  app->route->get('/',
    function () {
      print app->view('layout', [
        'title' => "Home page",
        'content' => "This is the home page.",
      ]);
    }
  );

  app->route->get('/hello/<name?>',
    fn ($name = 'world') => print app->view('layout', [
      'title' => "Hello, $name",
      'content' => null,
    ])
  );

  app->route->get('/goodbye/<goodbye?string>/<times:int>',
    fn ($times, $goodbye = 'goodbye') => print app->view('layout', [
      'title' => "Goodbye...",
      'content' => str_repeat(
        "<p>So long, farewell, auf Wiedersehen, $goodbye!</p>",
        $times
      ),
    ])
  );

  app->route->get('/favorite-things',
    fn () => print app->view('layout', [
      'title' => "A few of my favorite things",
      'content' => app->view('partials/list', [
        'items' => [
          "raindrops on roses",
          "whiskers on kittens",
          "bright copper kettles",
          "warm woolen mittens",
          "brown paper packages tied up with strings",
        ],
      ]),
    ])
  );

  app->route->post('/apply-now',
    function () {
      $data = json_decode(app->input);
      $name = htmlspecialchars(@$data->name);
      $role = htmlspecialchars(@$data->role);
      if ($name && $role) {
        print app->view('layout', [
          'title' => "Thank you, $name",
          'content' => "Your application for the role of $role is being considered."
        ]);
      } else {
        http_response_code(400);  // Bad Request
        print app->view('layout', [
          'title' => "Sorry",
          'content' => "There was a problem with your application; please try again."
        ]);
      }
    }
  );

  // Fallback:

  http_response_code(404);  // Not Found
  print '<h1>404 Not Found :(</h1>';
  ```

- Start a web server that points at `public/index.php`:
  - For development, the easiest option is to run the built-in server:
    ```sh
    php -S localhost:8000 -t public
    ```
  - For production environments, you'll probably want to set up [nginx](https://nginx.org/en/) or [Apache](https://httpd.apache.org/).

- Then, from your base URL (e.g. `http://localhost:8000`), try out some routes in your browser of choice:
  ```
  /
  /hello
  /hello/captain
  /goodbye/5
  /goodbye/adieu/3
  /goodbye/goodnight
  /favorite-things
  ```

  Or with [curl](https://curl.se/):
  ```sh
  curl localhost:8000/apply-now -d '{"name": "Maria", "role": "Governess"}'
  ```

Note that in each case, the route handling methods `get()` and `post()` - or `put()`, `patch()`, `delete()`, whatever you need - take two arguments:

- The route pattern, which may contain parameters in angle brackets: these can be made optional with `?`, and/or provided with the name of a PHP scalar type as a syntax hint (though type coercion/URL decoding is not performed automatically, for security reasons)
- The callback function, with named parameters that correspond to those in the route pattern, where the app's `input` property may be used to inspect the HTTP request body

Hopefully you might already have an idea how this works by now...

**Yeah, looks simple enough - what's this `'view' => dir('../templates')` all about though?**

This tells your [`App`](src/App.php) instance where to find the PHP templates for generating your site's HTML; the framework isn't expecting any particular folder structure, so you need to tell it which base folder to look in. In fact, the array key doesn't even have to be named `view`: you could call it `template` instead, or `html`, or whatever you prefer! And you can even have multiple array keys, each referring to a different base folder.

Whichever names you choose will be used to create new callable [`Resource`](src/Resource.php) properties on the app instance: when invoked with a file path (relative to the base folder), these create anonymous class objects which `include` the file whenever used as a string, and which `extract` an optional array of variable names and values for use within that file's context, e.g.:

```php
<?php

define('app', new \Framework\App([
  'layout' => dir('../templates/layouts'),
  'partial' => dir('../templates/partials'),
]));

print app->layout('song/lyrics', [
  'title' => "The Lonely Goatherd",
  'content' => implode("<br>", [
    "High on a hill was a lonely goatherd,",
    app->partial('yodel.php'),
    "Loud was the voice of the lonely goatherd,",
    app->partial('yodel.php', ['short' => true]),
    "Folks in a town that was quite remote heard",
    app->partial('yodel'),  // adds `.php` if no suffix given
    "Lusty and clear from the goatherd's throat, heard",
    app->partial('yodel', ['short' => true]),
  ]),
]);
```

But that's not all! Besides base folders wrapped in `dir()`, you can add/overwrite anything you like to your `App` instance, at any time, either during instantiation or by calling the `use()` method:

```php
<?php

define('app', new \Framework\App([
  'notes' => ["Do", "Re", "Mi",],
  'lineFrom' => fn ($a, $b) => "$a - $b,\n",
]));

print "When you read you begin with A-B-C.\n";
print "When you sing you begin with " . implode("-", app->notes) . ".\n";

app->use([
  'notes' => ["Do", "Re", "Mi", "Fa", "So", "La", "Ti",],
  'memory_aids' => [
    "a deer, a female deer",
    "a drop of golden sun",
    "a name I call myself",
    "a long, long way to run",
    "a needle pulling thread",
    "a note to follow So",
    "a drink with jam and bread",
  ],
]);

foreach (array_combine(app->notes, app->memory_aids) as $note => $memory_aid) {
  print app->lineFrom($note, $memory_aid);
}
print "That will bring us back to " . app->notes[0] . "!";
```

**Cute. What else can it do?**

It not only handles requests, it can also make them:

```php
<?php

define('app', new \Framework\App());

app->route->get('/html',
  fn () => print app->request->get(
    'https://github.com/jhuddle/framework/blob/main/README.md'
  )
);

app->route->get('/json/<data:string>',
  function ($data) {
    $round_trip = app->request->post(
      'https://httpbin.org/anything',
      [
        'Content-Type' => 'text/plain',
        'X-Powered-By' => 'jhuddle/framework',
      ],
      $data
    );
    print "<h1>" . $round_trip->headers['Content-Type'] . "</h1>";
    print "<pre>" . $round_trip . "</pre>";
  }
);
```

Behind the scenes, the [`route`](src/Route.php) and [`request`](src/Request.php) objects were loaded into the app in just the same way as we've seen above - so it's possible to override them in the constructor or with `use()`, if you want to use a different implementation.

Same goes for [`db`](src/Database.php) - if the right environment variables are available in the execution context, the framework will establish a PDO connection based on the value of `DBMS`, using `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER` and `DB_PASS` for credentials. The included implementation comes with a couple of common helper methods for prepared statements and transactions, but you can override `db` with another if you like:

```php
<?php

// Using default implementation with environment variables
// `DBMS=mariadb` + `DB_` credentials (see above):

define('app', new \Framework\App());

$something_good = app->db->execute(
  '
    SELECT thing
    FROM youth INNER JOIN childhood ON youth.person_id = childhood.person_id
    WHERE thing LIKE ?
    LIMIT 1
  ',
  ['%good%']
);

// Switching to SQLite:

app->use([
  'db' => new \PDO('sqlite:nothing.sqlite')
]));

$query = app->db->query('
  SELECT thing
  FROM youth INNER JOIN childhood ON youth.person_id = childhood.person_id
  WHERE thing IS NULL
');

$nothing = $query->fetchAll(\PDO::FETCH_OBJ);
```

And there's also [`session`](src/Session.php), a very simple wrapper for the built-in PHP session management functions - by default, the framework starts a session unless `false` is passed as the second argument to the `App` constructor.

**OK, sounds good - is that everything?**

Yes and no! We've now touched each of the six small classes that make up this framework, but there's a lot more you can do with them...

- The objects you create via `dir()` can be stored in variables and extended with the `use()` method, just like the app itself:

  `templates/mantra.php`
  ```html
  <p>I have <?= $attribute ?> in <?= $item ?>!</p>
  ```

  `app/app.php`
  ```php
  <?php

  define('app', new \Framework\App([
    'view' => dir('../templates'),
  ]));

  $confidence = app->view('mantra', [
    'attribute' => 'confidence',
    'item' => 'me',
  ]);

  app->route->get('/confidence/<item>',
    fn ($item) => print $confidence->use(['item' => $item])
  );
  ```

- These objects are also callable! You can use this fact to simplify your route callbacks:

  `templates/hills.php`
  ```html
  <p>The hills are alive with the sound of <?= $noise ?>...</p>
  ```

  `app/app.php`
  ```php
  <?php

  define('app', new \Framework\App([
    'view' => dir('../templates'),
  ]));

  app->route->get('/sound/<noise?>',
    app->view('hills', ['noise' => 'music'])
  );
  ```

- Templates can also create and call these objects:

  `templates/layout.php`
  ```html
  <!DOCTYPE html>
  <html>
    <head>
      <meta charset="utf-8">
      <meta name="viewport" content="width=device-width, initial-scale=1">
      <title><?= $title ?></title>
    </head>
    <body>
      <header>
        <?= app->view('partials/header', ['title' => $title]) ?>
      </header>
      <main>
        <?= $content ?>
      </main>
      <footer>
        <?= app->view('partials/footer') ?>
      </footer>
    </body>
  </html>
  ```

  `templates/partials/header.php`
  ```html
  <h1><?= $title ?></h1>
  ```

  `templates/partials/footer.php`
  ```html
  <aside>Fun fact: not the national anthem of Austria.</aside>
  ```

  `app/app.php`
  ```php
  <?php

  define('app', new \Framework\App([
    'view' => dir('../templates'),
  ]));

  app->route->get('/edelweiss',
    app->view('layout', [
      'title' => "Edelweiss",
      'content' => "<p>Every morning you greet me!<p>",
    ])
  );
  ```

- Route methods can be chained together and/or prefixed:

  ```php
  <?php

  class RelationshipController {

    public static function depend() {
      print "I'll depend on you";
    }

    public static function takeCare() {
      print "I'll take care of you";
    }

    public static function prepare(string $men) {
      $sanitized_men = htmlspecialchars(urldecode($men));
      print "$sanitized_men - what do I know of those?";
    }

  }

  define('app', new \Framework\App());

  app->route->prefix('sixteen')
    ->get('/seventeen', [RelationshipController::class, 'depend'])
    ->get('/eighteen', [RelationshipController::class, 'takeCare'])
    ->get('/<men:string>', [RelationshipController::class, 'prepare'])
  ;
  ```

And there's probably a bunch of other possible patterns for a bunch of other use cases that I didn't think of yet... the goal of this project is to write the smallest, least-opinionated, most extensible and most useful PHP framework possible, so it's over to you and your imagination from here!

**Awesome, thank you! How can I contribute?**

The best contribution you can make to this framework is simply to use it, and report any issues... and if you build something cool with it, please let me know by adding a link to your GitHub repo/project website in a pull request to the [Projects](PROJECTS.md) file! It'd be great to see what you come up with. 🙂
