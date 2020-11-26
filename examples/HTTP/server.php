<?php

include __DIR__ . '../../../vendor/autoload.php';

use Rubix\ML\Datasets\Generators\Blob;
use Rubix\ML\Datasets\Generators\Agglomerate;
use Rubix\ML\Classifiers\KDNeighbors;
use Rubix\Server\HTTPServer;
use Rubix\Server\HTTP\Middleware\AccessLogGenerator;
use Rubix\ML\Other\Loggers\Screen;

$generator = new Agglomerate([
    'red' => new Blob([255, 0, 0], 10.0),
    'green' => new Blob([0, 128, 0], 10.0),
    'blue' => new Blob([0, 0, 255], 10.0),
]);

$estimator = new KDNeighbors(5);

$dataset = $generator->generate(100);

$estimator->train($dataset);

$server = new HTTPServer('127.0.0.1', 8080, null, [
    new AccessLogGenerator(new Screen()),
]);

$server->setLogger(new Screen());

$server->serve($estimator);