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
