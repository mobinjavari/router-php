<div align="center">
  <h1>Simple PHP Router</h1>
  <p>The Best Router With PHP (<a href="./src/Router.php" title="Source Code"><small>use</small></a>)</p>
</div><br>

<p>routers.php</p>

```php
// [*] Creating a new instance of Router
$router = new Router();

// [!] Set the base address (example.com/{project_folder})
$router->setBasePath('/router');

// [*] Adding a new route with the get method
$router->get('/test', function () {
        echo 'test';
});

// [!] Examples of other tasks that can be done with this class
$router->mountPath('/api', function() use ($router) {
     $router->any('/test/[:id]', function ($id) {
        echo 'test'.$id;
    });
    $router->any('/hello/[:nicname]', '/hello.php');
});

// [!] set 404 error
$router->setError(function () {
    echo '404 | Page Not Found'
});

// [*] And finally, at the end of the file, we run the following method to run and check all the routes
$router->matchRoute();
```

<p>hello.php</p>

```php
echo 'Hello ' . $nicname;
```
