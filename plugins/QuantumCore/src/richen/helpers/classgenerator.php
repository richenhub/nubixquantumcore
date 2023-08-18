<?php 

namespace richen\helpers;

class classgenerator {
    public function generateCommand(string $name) {
        $class = "<?php

namespace richen\plugin\commands;

class {$name}_command
{
    public function __construct()
    {

    }
}
";
        
        $filename = "{$name}_command.php";
        file_put_contents(dirname(dirname(__DIR__)) . '/richen/plugin/commands/' . $filename, $class);
    }
}