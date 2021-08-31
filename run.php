<?php

declare(strict_types=1);

require_once 'vendor/autoload.php';

use DI\Container;
use Symfony\Component\Console\Application;
use TwitchDiscordFinder\ReconCommand;
use TwitchDiscordFinder\RunCommand;

$container = new Container();
$application = new Application();
$application->add($container->get(RunCommand::class));
$application->add($container->get(ReconCommand::class));
$application->run();
