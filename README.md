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

- PHP: Version 7.3+
- [Composer](https://getcomposer.org/) (If you don't have it installed already, start by [installing it](https://getcomposer.org/doc/00-intro.md))

For this particular tutorial you will need:

- MySQL: 8.0+

### Installing the framework

To install the framework using Composer and jump into the project's directory run the following command in your terminal:

```bash
composer create-project gekko/skel --repository="{ \"type\": \"vcs\", \"url\": \"https://github.com/gekko-framework/skel\" }" web-app 0.1.* && cd web-app
```

### Preparing the **.dev** directory

The **.dev** directory will contain all the necessary files to build the foundation of our application:

```bash
mkdir .dev && cd .dev
```

Now let's create subfolders for the different resources we will need:

```bash
mkdir model        # Here we will put our model's resources
mkdir migrations   # All the db migration stuff will be placed here
mkdir bin          # Gekko uses console commands for different tasks, we will put our custom commands here
```

### Creating the model

The following snippet defines a `PackageDescriptor` that contains all the information about the database, the tables, and their columns. With the same `PackageDescriptor` **Gekko** will be able to generate the PHP classes for this package.

Create a new file called `package.php` within the `.dev/model` folder with the snippet's content:

```php
<?php // file: .dev/model/package.php
use \Gekko\Model\PackageDescriptor;

// Create a package (namespace)
$package = new PackageDescriptor("WebApp");

// Define the schema for the DB
$package->schema("web_app");

// Create a User model
$user = $package->model("User");

// Set the nested namespace name within the "WebApp" root namespace
$user->namespace("Domain\\Model");

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

Now that we have our `PackageDescriptor`, we need to generate the different sources to build our application stack. In order to do that, we will create a custom `Command`. Create a file called `GenerateDomainCommand.php` within the `.dev/bin` folder and paste the following code in that file:

```php
<?php // file: .dev/bin/GenerateDomainCommand.php
namespace Dev\Bin;

use \Gekko\Env;
use \Gekko\Console\{ ConsoleContext, Command};
use \Gekko\Model\Generators\Runner;
use \Gekko\Model\Generators\Domain\DomainGenerator;
use \Gekko\Model\Generators\MySQL\Schema\MySQLSchemaGenerator;
use \Gekko\Model\Generators\MySQL\Mappers\MySQLDataMapperGenerator;
use \Gekko\Model\Generators\MySQL\Repositories\MySQLRepositoryGenerator;

class GenerateDomainCommand extends Command
{
    public function run(ConsoleContext $ctx) : int
    {
        // A Runner is simply that, a class that register different generators and run them in the order they were registered
        $runner = new Runner();

        // The first thing we need, is the database schema. For this example we will use MySQL.
        // The output of the generator will be placed within the migrations folder that will be created
        // in the project's root
        $runner->register(new MySQLSchemaGenerator(Env::getRootDirectory() . "/.dev/migrations"));

        // Next generator is the one that will create all the PHP Classes.
        $runner->register(new DomainGenerator(DomainGenerator::GEN_CLASS, Env::getRootDirectory()));

        // We need to generate the mappers that glue our schema with our classes.
        $runner->register(new MySQLDataMapperGenerator(MySQLDataMapperGenerator::GEN_CLASS, Env::getRootDirectory()));

        // Generate the repositories
        $runner->register(new MySQLRepositoryGenerator(MySQLRepositoryGenerator::GEN_CLASS, Env::getRootDirectory()));

        // Now we need to get the reference to our `PackageDescriptor`
        $package = require Env::getRootDirectory() . "/.dev/model/package.php";

        // We run the generators, and all the sources should be placed in the `output` directory
        $runner->run($package);

        return 0;
    }
}
```

Our `GenerateDomainCommand` is almost ready to be used, we just need to do two more things: 
- we need to register the console application to run it with the `gko` command
- we need to update the `composer.json` file to map the `Dev` namespace to the `.dev` directory

### Preparing the configuration

There is a file called `.env.example` in the root of the project, what we need to do is to create a copy of that file and rename it to `.env`: 

```bash
cp .env.example .env
```

You can check the content of that file, but for the purpose of this example we will use the default values that configure where the config files should be placed, and what driver will Gekko use to load the configuration.

Now that we have our *configuration's configuration* in place, we need to modify 1 files inside the `config` folder:

- `console.php`: We need to associate our `GenerateDomainCommand` application to an *application name*

It should look like this:

```php
<?php
return [
    "bin" => [
        // Gekko's built-in commands
        "php-server"    => Gekko\Console\PHP\ServerCommand::class,
        "php-cgi"       => Gekko\Console\PHP\FastCGICommand::class,
        "nginx"         => Gekko\Console\Nginx\ServerCommand::class,

        // Put your custom commands here
        // ...
    ]
];
```
To register our `GenerateDomainCommand`, we need to associate it to a command name, we will use the `generate` command name for it:

```php
<?php // file: config/console.php
return [
    "bin" => [
        // Gekko's built-in commands
        "php-server"    => Gekko\Console\PHP\ServerCommand::class,
        "php-cgi"       => Gekko\Console\PHP\FastCGICommand::class,
        "nginx"         => Gekko\Console\Nginx\ServerCommand::class,

        // Put your custom commands here
        "generate" => \Dev\Bin\GenerateDomainCommand::class
    ]
];
```

We registered our new command, but we still need to map the `Dev` namespace with the `.dev` directory in order to resolve the classes that belong to that namespace. To do that, we will update the `composer.json` file adding the `autoload-dev` object with our mapping. It should look like this:

```php
<?php // file: composer.json
{
    // ...
    "autoload-dev": {
        "psr-4": {
            "Dev\\": ".dev/"
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

Now if you look into the `.dev/migrations` folder you should see 2 files: `v1.json` and `.versions`. Also you should see that under the `App` folder you should find the `Domain` folder that contains our model.

### Understanding the generated output

Our console application has generated a lot of stuff, what should we do now with that? Let's start for the easiest part.

#### Model

If you navigate through the `App/Domain` folder you will see something similar in the structure with something we did before: The hierarchy resembles the namespace we registered in the `PackageDescriptor`. If you navigate to the `Model` folder, you will see the following `php` files:

- `User.php`: This is the class that represents our `User` model
- `Descriptors/UserDescriptor.php`: This class contains `metadata` about our model, it is useful for other classes.
- `DataMappers/UserDataMapper.php`: This is the class that knows how to retrieve a `User` object from the database, and also knows how to store a `User` in the DB. To achieve that, the mapper uses the `UserDescriptor` class.
- `Repositories/UserRepository.php`: This class uses the `UserDataMapper` class to store, remove, and query `User` objects from the database.

**NOTE**: By default, Gekko associates the `App\\` namespace with the `App/` folder, therefore as long as you use a namespace that resembles the *default* structure suggested in this tutorial, it shouldn't be necessary to update Composer's autoload, but if you want to use a different structure you will need to update it.

#### Migrations

As mentioned before, our `MySQLSchemaGenerator` has generated a `json` file with a suggestive name: `v1.json`. This `json` file is nothing more and nothing less than the *database descriptor*, this is, our `PackageDescriptor` serialized to a `json` file containing just the information the migration process needs in order to work properly.

Similar to what we did when we created the `GenerateDomainCommand` we now need to create a `MigrationCommand` console application. This application will allow us to get full control of the migration process:

```php
<?php // file: .dev/bin/MigrationCommand.php

namespace Dev\Bin;

use Gekko\Env;
use Gekko\Database\MySQL\MySQLConnection;
use \Gekko\Console\{ ConsoleContext, Command};
use Gekko\Database\MySQL\Migrations\MySQLMigration;

class MigrationCommand extends Command
{
    public function run(ConsoleContext $ctx) : int
    {
        $dbconfig = $ctx->getConfigProvider()->getConfig("database");
        $connection = new MySQLConnection($dbconfig->get("mysql.connection.host"), null, $dbconfig->get("mysql.connection.user"), $dbconfig->get("mysql.connection.pass"));

        $migrationManager = new MySQLMigration($connection, Env::getRootDirectory() . "/.dev/migrations");
               
        $lastVersion = $migrationManager->getLastVersion();
        $migrationManager->upgradeTo($lastVersion);

        return 0;
    }
}
```

As we did with our previous `Command`, we need to register it in the `console.php` file:

```php
<?php // file: config/console.php
return [
    "bin" => [
        // Gekko's built-in commands
        "php-server"    => Gekko\Console\PHP\ServerCommand::class,
        "php-cgi"       => Gekko\Console\PHP\FastCGICommand::class,
        "nginx"         => Gekko\Console\Nginx\ServerCommand::class,

        // Put your custom commands here
        "generate" => \Dev\Bin\GenerateDomainCommand::class,
        "migrate" => \Dev\Bin\MigrationCommand::class
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

If the `echo` outputs a `0`, it means the migration ran successfully, and we should have our database and table ready, but just to be sure you can check it with the following command:

```bash
mysql -u <db username> -p -e "SELECT TABLE_SCHEMA, TABLE_NAME FROM information_schema.tables WHERE table_schema = 'web_app' AND table_name = 'users' LIMIT 1;"
Enter password: ************

+--------------+------------+
| TABLE_SCHEMA | TABLE_NAME |
+--------------+------------+
| web_app      | users      |
+--------------+------------+
```

#### Seeding our database

We will create a new `Command` to insert records in our database. Let's create a file named `DbSeedCommand` inside our `.dev/bin` folder, and make it looks like this:

```php
<?php // file: .dev/bin/DbSeedCommand.php

namespace Dev\Bin;

use \WebApp\Domain\Model\User;
use \Gekko\Console\{ ConsoleContext, Command};
use \Gekko\Database\MySQL\MySQLConnection;
use \WebApp\Domain\Model\Repositories\UserRepository;

class DbSeedCommand extends Command
{
    public function run(ConsoleContext $ctx) : int
    {
        // Instantiate the UserDataMapper
        $dbconfig = $ctx->getConfigProvider()->getConfig("database");

        $connection = new MySQLConnection(
                $dbconfig->get("mysql.connection.host"), 
                $dbconfig->get("mysql.connection.name"), 
                $dbconfig->get("mysql.connection.user"), 
                $dbconfig->get("mysql.connection.pass"));

        $userRepository = new UserRepository($connection);

        for ($i=0; $i < 3; $i++)
        {
            // Create and populate a new User
            $user = new User();
            $user->setUsername("User $i");
            $user->setEmail("user.$i@mail.com");

            // Insert the newly created user
            $userRepository->add($user);
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
        // Gekko's built-in commands
        "php-server"    => Gekko\Console\PHP\ServerCommand::class,
        "php-cgi"       => Gekko\Console\PHP\FastCGICommand::class,
        "nginx"         => Gekko\Console\Nginx\ServerCommand::class,

        // Put your custom commands here
        "generate" => \Dev\Bin\GenerateDomainCommand::class,
        "migrate" => \Dev\Bin\MigrationCommand::class,
        "seed" => \Dev\Bin\DbSeedCommand::class
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
        \Gekko\Config\IConfigProvider $configProvider
    ) {
        // We se the response's Content-Type
        $response->setHeader("Content-Type", "application/json");

        // We create the MySQL connection and we create the user repository
        $dbconfig = $configProvider->getConfig("database");

        $connection = new \Gekko\Database\MySQL\MySQLConnection(
            $dbconfig->get("mysql.connection.host"), 
            $dbconfig->get("mysql.connection.name"), 
            $dbconfig->get("mysql.connection.user"), 
            $dbconfig->get("mysql.connection.pass"));

        $userRepository = new \WebApp\Domain\Model\Repositories\UserRepository($connection);

        // We retrieve the list of users
        $users = $userRepository->getAll();

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
