<?php

namespace Rubix\Server;

use Rubix\ML\Estimator;
use Rubix\ML\Probabilistic;
use FastRoute\RouteCollector;
use FastRoute\RouteParser\Std as Parser;
use FastRoute\DataGenerator\GroupCountBased as DataGenerator;
use FastRoute\Dispatcher\GroupCountBased as Dispatcher;
use Rubix\Server\Controllers\Proba;
use Rubix\Server\Controllers\Predict;
use React\Http\Server as ReactServer;
use React\Socket\Server as Socket;
use React\EventLoop\Factory as Loop;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use React\Http\Response as ReactResponse;
use InvalidArgumentException;

class HTTPServer implements Server
{
    /**
     * The host to bind the server to.
     * 
     * @var string
     */
    protected $host;

    /**
     * The port to run the http services on.
     * 
     * @var int
     */
    protected $port;

    /**
     * The controller dispatcher i.e the router.
     * 
     * @var Dispatcher
     */
    protected $router;

    /**
     * @param  array  $routes
     * @param  string  $host
     * @param  int  $port
     * @throws \InvalidArgumentException
     * @return void
     */
    public function __construct(array $routes, string $host = '127.0.0.1', int $port = 8888)
    {
        $collector = new RouteCollector(new Parser(), new DataGenerator());

        foreach ($routes as $uri => $estimator) {
            if (!is_string($uri) or empty($uri)) {
                throw new InvalidArgumentException('URI must be a non empty'
                    . ' string ' . gettype($uri) . ' found.');
            }

            if (!$estimator instanceof Estimator) {
                throw new InvalidArgumentException('Route must point to'
                    . ' an Estimator instance, ' . get_class($estimator)
                    . ' found.');
            }

            $collector->addGroup($uri, function (RouteCollector $r) use ($estimator) {
                $r->addRoute('POST', '/predict', new Predict($estimator));

                if ($estimator instanceof Probabilistic) {
                    $r->addRoute('POST', '/proba', new Proba($estimator));
                }
            });
        }

        if ($port < 0) {
            throw new InvalidArgumentException('Port number must be'
                . " positive, $port given.");
        }

        $this->host = $host;
        $this->port = $port;
        $this->router = new Dispatcher($collector->getData());
    }

    /**
     * Boot up the server.
     * 
     * @return void
     */
    public function run() : void
    {
        $loop = Loop::create();

        $socket = new Socket("$this->host:$this->port", $loop);

        $server = new ReactServer([$this, 'handle']);

        $server->listen($socket);

        $loop->run();
    }

    /**
     * Handle an incoming request.
     * 
     * @param  Request  $request
     * @return Response
     */
    public function handle(Request $request) : Response
    {
        $uri = $request->getUri()->getPath();
        $method = $request->getMethod();

        list($status, $controller, $params) = $this->router->dispatch($method, $uri);

        switch ($status) {
            case Dispatcher::NOT_FOUND:
                return new ReactResponse(404);

            case Dispatcher::METHOD_NOT_ALLOWED:
                return new ReactResponse(405);
        }

        return $controller->handle($request, $params);
    }
}