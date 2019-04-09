# Gekko Skeleton

## What is it?

This is the skeleton project to start a new application using the [Gekko framework](https://github.com/gekko-framework/core).

## How can I start a new project?

In the following example you will be learning how to create a new project and make use of the many features **Gekko** provides:

- *Scaffolding*: Write your model using Gekko's powerful descriptors and build your entire stack using it.
- *Migrations*: Gekko provides a simple yet powerful migration tool that you can use on your own to get more control over how the database updates occur.
- *Configuration*: An easy to use, easy to extend configuration system.
- *Console tools*: Gekko makes extremely easy the creation and usage of satellite console tools to extend your applications building process.
- *Data mapping*: A simple yet powerful data mapper to store and retrieve your entities from the data storage layer.
- *Routing*: Build your API based on your needs, create different routing maps to easily switch between different version.
- *Dependency Injection*: Gekko's powerful DI library makes easy working with dependencies and extending core functionalities.

### Requisites

Gekko's requisites are

- PHP: Version 7.2+
- [Composer](https://getcomposer.org/) (If you don't have it installed already, start by [installing it](https://getcomposer.org/doc/00-intro.md))

For this particular tutorial you will need:

- MySQL: 8.0+

### Installing the framework

To install the framework using Composer and jump into the project's directory run the following command in your terminal:

```bash
composer create-project gekko/skel --repository="{ \"type\": \"vcs\", \"url\": \"https://github.com/gekko-framework/skel\" }" web-app 0.1.* && cd web-app
```

### Preparing the **dev**

The **dev** folder will contain all the necessary files to build the foundation of our application:

```bash
mkdir dev
```

### Creating the model

The following snippet defines a `PackageDescriptor` that contains all the information about the database, the tables, and their columns. With the same `PackageDescriptor` **Gekko** will be able to generate the PHP classes for this package.

Create a new file called `package.php` within the `dev` folder with the snippet's content:

```php
<?php // file: dev/package.php
use \Gekko\Model\PackageDescriptor;

// Create a package (namespace)
$package = new PackageDescriptor("WebApp");

// Define the schema for the DB
$package->schema("web_app");

// Create a User model
$user = $package->model("User");

// Set the namespace
$user->namespace("App\Data\\{$package->name}\Model");

// Set the table name for this model
$user->tableName("users");

// Create the properties
$userPk = $user->property("id")
        ->int32()
        ->autoincrement()
        ->key();

$user->property("username")
        ->string()
        ->length(500)
        ->unique();

$user->property("email")
        ->string()
        ->length(255)
        ->unique();

return $package;
```

### Creating a console application to generate all the scaffolding

Now that we have our `PackageDescriptor`, we need to generate the different sources to build our application stack. In order to do that, we will create a `Command`

```php
<?php // file: dev/WebAppGenerator.php
namespace Dev;

use \Gekko\Env;
use \Gekko\Model\Generators\Runner;
use \Gekko\Console\Command;
use \Gekko\Model\Generators\Domain\DomainGenerator;
use \Gekko\Model\Generators\MySQL\Schema\MySQLSchemaGenerator;
use \Gekko\Model\Generators\MySQL\Mappers\MySQLDataMapperGenerator;

class WebAppGenerator extends Command
{
    public function run() : int
    {
        // A Runner is simply that, a class that register different generators and run them in the order they were registered
        $runner = new Runner();

        // The first thing we need, is the database schema. For this example we will use MySQL.
        // The output of the generator will be placed within the migrations folder that will be created
        // in the project's root
        $runner->register(new MySQLSchemaGenerator(Env::rootDir() . DIRECTORY_SEPARATOR . "migrations"));

        // Next generator is the one that will create all the PHP Classes. 
        // (The output will be placed in the project's root folder making honor of the namespace)
        $runner->register(new DomainGenerator(Env::rootDir(), DomainGenerator::GEN_CLASS));

        // Finally we need to generate the mappers that glue our schema with our classes.
        // (The output will be placed in the project's root folder making honor of the namespace)
        $runner->register(new MySQLDataMapperGenerator(Env::rootDir(), MySQLDataMapperGenerator::GEN_CLASS));

        // Now we need to get the reference to our `PackageDescriptor`
        $package = require "package.php";

        // We run the generators, and all the sources should be placed in the `output` directory
        $runner->run($package);

        return 0;
    }
}
```

Our `WebAppGenerator` is ready to be used, we just need to do one more thing, we need to register this console application, and to fully accomplish that we need to configure our application.

### Preparing the configuration

Our project contains a `.env.example` file, what we need to do is to create a copy of that file and rename it to `.env`: 

```bash
cp .env.example .env
```

You can check the content of that file, but for the purpose of this example we will use the default values that configure where the config files should be placed, and what driver will Gekko use to load the configuration.

Now that we have our *configuration's configuration* in place, we need to create 1 files inside the `config` folder:

- `console.php`: We need to associate our `WebAppGenerator` application to an *application name*

```php
<?php // file: config/console.php
return [
    "bin" => [
        "generate" => \Dev\WebAppGenerator::class,
    ]
];
```

We registered our new command, but there is something missing: we also need to register our `Dev` namespace with the folder `dev` in order to resolve the classes that belong to that namespace.

To accomplish that, we will update the `composer.json` file adding the `autoload-dev` object with our mapping. It should look like this:

```php
<?php // file: composer.json
{
    // ...
    "autoload-dev": {
        "psr-4": {
            "Dev\\": "dev/"
        }
    }
    // ...
}
```

After that we run the following command to [update Composer's autoload](https://getcomposer.org/doc/03-cli.md#dump-autoload-dumpautoload-):

```bash
composer dump-autoload
```

If everything is in place, we should be able to run the `gko generate` command to generate our application's scaffolding:

```bash
./gko generate # or: <path-to-php>/php gko generate
echo $? # we expect to get 0 here
```

Now if you look into the project's root folder, you will see the new `migrations` folder. Also you should see that under the `App` folder you should find the `Data` folder that contains our model.

### Understanding the generated output

Our console application has generated a lot of stuff, what should we do now with that? Let's start for the easiest part.

#### Model

If you navigate through the `App/Data` folder you will see something similar in the structure with something we did before: The hierarchy resembles the namespace we registered in the `PackageDescriptor`. If you navigate to the `Model` folder, you will see 3 `php` files:

- `User.php`: This is the class that represents our `User` model
- `Descriptors/UserDescriptor.php`: This class contains `metadata` about our model, it is useful for other classes.
- `DataMappers/UserDataMapper.php`: This is the class that knows how to retrieve a `User` object from the database, and also knows how to store a `User` in the DB. To achieve that, the mapper uses the `UserDescriptor` class.

**NOTE**: By default, Gekko associates the `App\\` namespace with the `App/` folder, therefore as long as you use a namespace that resembles the *default* structure suggested in this tutorial, it shouldn't be necessary to update Composer's autload, but if you want to use a different structure you will need to update it.

#### Migrations

Our `MySQLSchemaGenerator` has generated a folder named `migrations` that contains a `json` file with a suggestive name: `v1.json`. This `json` file is nothing more and nothing less than the *database descriptor*, this is, our `PackageDescriptor` serialized to a `json` file containing just the information the migration process needs in order to work properly.

Similar to what we did when we created the `WebAppGenerator` we now need to create a `WebAppMigration` console application. This application will allow us to get full control of the migration process:

```php
<?php // file: dev/WebAppMigration.php

namespace Dev;

use \Gekko\Env;
use \Gekko\Console\Command;
use \Gekko\Database\MySQL\Migrations\MySQLMigration;

class WebAppMigration extends Command
{
    public function run() : int
    {
        // Instantiate the MySQLMigration object with the path where the migrations reside
        $migrationsPath = Env::rootDir() . DIRECTORY_SEPARATOR . "migrations";
        $migration = new MySQLMigration($migrationsPath);

        // Run all the migrations until reach the last available version
        $migration->upgradeTo($migration->getLastVersion());

        return 0;
    }
}
```

As we did with our previous `Command`, we need to register it in the `console.php` file:

```php
<?php // file: config/console.php
return [
    "bin" => [
        "generate" => \Dev\WebAppGenerator::class,
        "migrate" => \Dev\WebAppMigration::class
    ]
];
```

Now, before proceeding to run our application, we need to do one more thing that is critical: create our database config file. Easy peasy:

```php
<?php // file: config/database.php
return [
    "mysql" => [
        "connection" => [
            "host" => "localhost",
            "name" => "web_app", // This is the schema name of our PackageDescriptor
            "user" => <db username>,
            "pass" => <db password>
        ]
    ]
];
```

Once we have all in place, we just need to run the following in the shell:

```bash
./gko migrate # or: <path-to-php>/php gko migrate
echo $? # we expect to get 0 here
```

If we are seeing a `0`, it means the migration run successfully, and we should have our database and table ready.

#### Seeding our database

We will create a new `Command` to insert records in our database. Let's create a file named `WebAppSeeder` inside our `dev` folder, and make it looks like this:

```php
<?php // file: dev/WebAppSeeder.php

namespace Dev;

use \Gekko\Console\Command;
use \App\Data\WebApp\Model\DataMappers\UserDataMapper;
use \App\Data\WebApp\Model\User;

class WebAppSeeder extends Command
{
    public function run() : int
    {
        // Instantiate the UserDataMapper
        $userMapper = new UserDataMapper();

        for ($i=0; $i < 3; $i++)
        {
            // Create and populate a new User
            $user = new User();
            $user->setUsername("User $i");
            $user->setEmail("user.$i@mail.com");

            // Insert the newly created user
            $userMapper->insert($user);
        }

        return 0;
    }
}
```

What's next? Registering our application in the `console.php` file:

```php
<?php // file: config/console.php
return [
    "bin" => [
        "generate" => \Dev\WebAppGenerator::class,
        "migrate" => \Dev\WebAppMigration::class,
        "seed" => \Dev\WebAppSeeder::class
    ]
];
```

I know what you are thinking: "`./gko seed` and we should get our records inserted in the DB". Well, you're right:

```bash
./gko seed # or: <path-to-php>/php gko migrate
echo $? # we expect to get 0 here
```

Now you can run the following query to see the inserted records:

```bash
mysql -u <db username> -p -e "SELECT * FROM web_app.users;"
Enter password: ************

+----+----------+-----------------+
| id | username | email           |
+----+----------+-----------------+
|  1 | User 0   | user.0@mail.com |
|  2 | User 1   | user.1@mail.com |
|  3 | User 2   | user.2@mail.com |
+----+----------+-----------------+
```

At this point we:

- Created the application model
- Generated the database and the classes
- Seeded the database with the users of our application

Now we should expose our resources to the world!

### Creating a Web Application

We have been using the `Command` class for all the tools we have created because all these commands are being run by the `ConsoleApplication` named `gko`.

Now it is time to create an `HttpApplication` to expose an endpoint to access our list of `users`.

#### Entry point

The `index.php` file should be the entry point for all the HTTP requests our application will receive. By default it should look like this:

```php
<?php // index.php

require __DIR__ . "/vendor/autoload.php";

// Create a new HTTP Application to route and dispatch the HTTP request
$app = new \Gekko\App\HttpApplication(realpath(__DIR__));

// magic goes here:
// ...

// Dispatch the request
$app->run();
```

An `HttpApplication` must receive the absolute path to the project's directory in order to work properly. The last statement of the script is the call to the `HttpApplication::run` method to dispatch the HTTP request.

#### Routing

Our application will need to register a routing map to be able to dispatch incoming HTTP requests, and we can accomplish that using the `RoutingMap` class:

```php
<?php // index.php

require __DIR__ . "/vendor/autoload.php";

// Create a new HTTP Application to route and dispatch the HTTP request
$app = new \Gekko\App\HttpApplication(realpath(__DIR__));

// Our API will be exposed under the /v1 URL
$routes = new \Gekko\Http\Routing\RoutingMap("/v1");

// ... here we will register our route

// Register the API route map
$app->withRoutes($routes);

// Dispatch the request
$app->run();
```

Now we have the routing map boilerplate ready, it's time to create the endpoint to retrieve the list of users:

```php
<?php // index.php

require __DIR__ . "/vendor/autoload.php";

// Create a new HTTP Application to route and dispatch the HTTP request
$app = new \Gekko\App\HttpApplication(realpath(__DIR__));

// Our API will be exposed under the /v1 URL
$routes = new \Gekko\Http\Routing\RoutingMap("/v1");

$routes->get("/users", function (\Gekko\Http\IHttpRequest $request, \Gekko\Http\IHttpResponse $response) {
    $response->setBody("{$request->getURI()}: Here we should place our users list");
    return $response;
});

// Register the API route map
$app->withRoutes($routes);

// Dispatch the request
$app->run();
```

At this point, our `HttpApplication` is ready to be started and our endpoint ready to be consumed.

#### Starting our Web Application

We will use the PHP integrated server, let's start it:

```bash
php -S localhost:8080 index.php

PHP 7.2.6 Development Server started at Sat Feb 9 10:46:49 2019
Listening on http://localhost:8080
Document root is <path to the project>
Press Ctrl-C to quit.
```

Our server is running and listening on port 8080. If everything is correct in our `App/index.php` file, we should be able to access our `/v1/users` resource. If you have curl try the following in a new shell:

```bash
curl -s http://localhost:8080/v1/users
# You should see the following text in the curl's output:
http://localhost:8080/v1/users: Here we should place our users' list
```

If you don't have curl just navigate to `http://localhost:8080/v1/users` and should see the same output.

Congratulations, you bootstrapped your application!

#### Retrieving the users list

Ok, that's awesome, we have all in place we just need to return the list of users. To do that, we will update our `/users` route:


```php
<?php // index.php

require __DIR__ . "/vendor/autoload.php";

// Create a new HTTP Application to route and dispatch the HTTP request
$app = new \Gekko\App\HttpApplication(realpath(__DIR__));

// Our API will be exposed under the /v1 URL
$routes = new \Gekko\Http\Routing\RoutingMap("/v1");

// We add a new parameter to the route, the `UserDataMapper` (the dependency injection will do its magic)
$routes->get(
    "/users", 
    function (
        \Gekko\Http\IHttpRequest $request, 
        \Gekko\Http\IHttpResponse $response, 
        \App\Data\WebApp\Model\DataMappers\UserDataMapper $userMapper
    ) {
        // We se the response's Content-Type
        $response->setHeader("Content-Type", "application/json");

        // We retrieve all the users using the DataMapper
        $users = $userMapper->selectAll();

        // Process our users lit
        $output = [];
        foreach ($users as $user)
        {
            $output[] = [
                "username" => $user->getUsername(),
                "email" => $user->getEmail()
            ];
        }

        // Return the JSON
        return json_encode($output);
    }
);

// Register the API route map
$app->withRoutes($routes);

// Dispatch the request
$app->run();
```

Great, let's try our endpoint one more time:

```bash
curl -s http://localhost:8080/v1/users
# You should see
[{"username":"User 0","email":"user.0@mail.com"},{"username":"User 1","email":"user.1@mail.com"},{"username":"User 2","email":"user.2@mail.com"}]
```

That's it! We built a web application from scratch using **Gekko**!