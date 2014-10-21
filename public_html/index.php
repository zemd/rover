<?php
require_once __DIR__."/../vendor/autoload.php";

use Rover\Services\RoverService;
use Silex\Provider\TwigServiceProvider;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

date_default_timezone_set('Europe/Kiev');

$app = new Silex\Application();
$app["rover"] = function() {
    return new RoverService();
};

$app->register(new TwigServiceProvider(), array(
    'twig.path' => __DIR__.'/../views',
));
$app->register(new Silex\Provider\DoctrineServiceProvider(), array(
    'db.options' => array(
        'driver'   => 'pdo_sqlite',
        'path'     => __DIR__.'/../db/rovers.db',
    ),
));
$app['debug'] = true;

$app->get("/init", function () use ($app) {
    $app['db']->prepare("CREATE TABLE rovers ( id INTEGER PRIMARY KEY, route CHAR(255), times CHAR(255), started CHAR(255) );")->execute();
    return new Response("OK");
});

$app->post('/go', function(Request $request) use ($app) {
	$markers = $request->get('markers');
	$times = $request->get('times');

	$stmt = $app['db']->prepare('INSERT INTO `rovers` (`route`, `times`, `started`) VALUES (?, ?, ?)');
    $stmt->execute(array(serialize($markers), serialize($times), date("Y-m-d H:i:s")));
    $res = $app['db']->lastInsertId();

    return new JsonResponse(base_convert($res * 100000, 10, 30));
});

$app->post("/get", function (Request $request) use ($app) {
    $id = intval(base_convert($request->get('id'), 30, 10)) / 100000;
    $res = $app["db"]->fetchAll("SELECT * FROM `rovers` WHERE id = ?", array($id));

    $route = unserialize($res[0]['route']);
    $times = unserialize($res[0]['times']);
    $date = new \DateTime($res[0]['started']);

    $data = array(
        'route' => $route,
        'times' => $times,
        'current' => $app["rover"]->getRoverPosition($route, $times, $date)
    );
    return new JsonResponse($data);
});

$app->run();
