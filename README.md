## Screenshot

![Screenshot](https://raw.githubusercontent.com/recca0120/laravel_parallel_test/master/examples/screenshot.gif)

## INSTALL

composer.json

```json
{
    "repositories": [
        {
            "type": "git",
            "url": "https://github.com/recca0120/laravel_parallel_test"
        }
    ],
    "require-dev": {
        "recca0120/parallel-test": "dev-master"
    }
}
```

## Example

routes/web.php

```php
Route::get('/', function () {
    sleep(10);

    return view('welcome');
});
```

tests/Feature/ExampleTest.php

```php
<?php

namespace Tests\Feature;

use GuzzleHttp\Promise\Utils;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Recca0120\ParallelTest\AsyncRequest;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     *
     * @return void
     */
    public function test_example()
    {
        $startAt = microtime(true);
        $asyncRequest = $this->app->get(AsyncRequest::class);
        $responses = Utils::unwrap(array_map(static function () use ($asyncRequest) {
            return $asyncRequest->get('/');
        }, range(0, 100)));

        foreach ($responses as $response) {
            $response->assertOk();
        }
        dump(microtime(true) - $startAt);
    }
}
```

## RESULT

```bash
Testing started at 3:03 下午 ...
PHPUnit 9.5.5 by Sebastian Bergmann and contributors.

11.854576826096


Time: 00:11.981, Memory: 30.00 MB

OK (1 test, 101 assertions)
Process finished with exit code 0
```
