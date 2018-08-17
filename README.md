Hamlet Core
===

[![Build Status](https://travis-ci.org/vasily-kartashov/hamlet-core.svg)](https://travis-ci.org/vasily-kartashov/hamlet-core)

### Some notes

* [Framework design](https://notes.kartashov.com/2016/07/08/simple-caching-web-framework/)
* [Data processing](https://notes.kartashov.com/2017/05/09/result-set-processor/)

### Bootstrapping an application with a Web Server

```php
require_once __DIR__ . '/../../vendor/autoload.php';

\Hamlet\Bootstraps\ServerBootstrap::run(function () {
    return new \Example\Application;
});
```

### Bootstrapping an application with Swoole Http Server

```php
require_once __DIR__ . '/../../vendor/autoload.php';

\Hamlet\Bootstraps\SwooleBootstrap::run('0.0.0.0', 8080, function () {
    return new \Example\Application;
});
```

### How to use database package:

Start with two tables 

```sql
CREATE TABLE users (
    id INTEGER
    name VARCHAR(255)
);

CREATE TABLE addresses (
    user_id INTEGER
    address VARCHAR(255)
);
```

We can map both tables to a single immutable entity like following:

```php
class User implements \Hamlet\Database\Entity
{
    private $id, $name, $addresses;
    
    public function id(): int 
    {
        return $this->id;
    }
    
    public function name(): string
    {
        return $this->name;
    }
    
    public function addresses(): array
    {
        return $this->addresses;
    }
}
```

The repository method that fetches `User` entity by ID would look like this:

```php
$database = Database::sqlite3(tempnam(sys_get_temp_dir(), '.sqlite'));

$procedure = $database->prepare('
    SELECT id, name, address
      FROM users
           JOIN addresses
             ON users.id = addresses.user_id
     WHERE id = ?
');
$procedure->bindInteger($userId);

return $procedure->processAll()
    ->selectValue('address')->groupInto('addresses')
    ->selectAll()->cast(User::class)
    ->collectHead();
```

And Bob's your uncle. Unless you need to chew through tens of 1000s of rows, in which case you'll need to process entities in a stream:

```php
...

return $procedure->stream()
    ->selectValue('address')->groupInto('addresses')
    ->selectAll()->cast(User::class)
    ->forEach(function (User $user) {
        echo $user . PHP_EOL;
    });
```

### To Do:

* Fix Request/Response sessions ping pong
* Swoole authentication + session handling
* Add more unit tests for travis
* Support for WebSockets
* Support for HTTP/2.0
* Support for OAuth server (PHP League)
