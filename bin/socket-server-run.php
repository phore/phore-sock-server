#!/usr/bin/php
<?php

use Phore\SockServer\Processor\ExampleSyslogProcessor;
use Phore\SockServer\SocketServer;

require __DIR__ . "/../vendor/autoload.php";

$socketServer = new SocketServer();
$socketServer->addProcessor(new ExampleSyslogProcessor());

$socketServer->run();


