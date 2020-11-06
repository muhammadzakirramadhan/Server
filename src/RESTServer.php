<?php

namespace Rubix\Server;

use Rubix\ML\Estimator;
use Rubix\ML\Learner;
use Rubix\ML\Probabilistic;
use Rubix\ML\Ranking;
use Rubix\Server\Services\Router;
use Rubix\Server\Services\CommandBus;
use Rubix\Server\Http\Middleware\Middleware;
use Rubix\Server\Http\Controllers\PredictionsController;
use Rubix\Server\Http\Controllers\SamplePredictionsController;
use Rubix\Server\Http\Controllers\ProbabilitiesController;
use Rubix\Server\Http\Controllers\SampleProbabilitiesController;
use Rubix\Server\Http\Controllers\ScoresController;
use Rubix\Server\Http\Controllers\SampleScoresController;
use Rubix\Server\Exceptions\InvalidArgumentException;
use Rubix\Server\Traits\LoggerAware;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use React\Http\Server as HTTPServer;
use React\Socket\Server as Socket;
use React\Socket\SecureServer as SecureSocket;
use React\EventLoop\Factory as Loop;
use Psr\Log\LoggerAwareInterface;

/**
 * HTTP Server
 *
 * A JSON over HTTP(S) server exposing a REST (Representational State Transfer) API. The REST
 * server exposes one endpoint (resource) per command and can be queried using any standard
 * HTTP client.
 *
 * @category    Machine Learning
 * @package     Rubix/Server
 * @author      Andrew DalPino
 */
class RESTServer implements Server, LoggerAwareInterface
{
    use LoggerAware;

    public const SERVER_NAME = 'Rubix REST Server';

    protected const MAX_TCP_PORT = 65535;

    /**
     * The host address to bind the server to.
     *
     * @var string
     */
    protected $host;

    /**
     * The network port to run the HTTP services on.
     *
     * @var int
     */
    protected $port;

    /**
     * The path to the certificate used to authenticate and encrypt the
     * secure (HTTPS) communication channel.
     *
     * @var string|null
     */
    protected $cert;

    /**
     * The HTTP middleware stack.
     *
     * @var \Rubix\Server\Http\Middleware\Middleware[]
     */
    protected $middlewares;

    /**
     * The router.
     *
     * @var \Rubix\Server\Services\Router
     */
    protected $router;

    /**
     * @param string $host
     * @param int $port
     * @param string|null $cert
     * @param mixed[] $middlewares
     * @throws \Rubix\Server\Exceptions\InvalidArgumentException
     */
    public function __construct(
        string $host = '127.0.0.1',
        int $port = 8080,
        ?string $cert = null,
        array $middlewares = []
    ) {
        if (empty($host)) {
            throw new InvalidArgumentException('Host cannot be empty.');
        }

        if ($port < 0 or $port > self::MAX_TCP_PORT) {
            throw new InvalidArgumentException('Port number must be'
                . ' between 0 and ' . self::MAX_TCP_PORT . ", $port given.");
        }

        if (isset($cert) and empty($cert)) {
            throw new InvalidArgumentException('Certificate cannot be empty.');
        }

        foreach ($middlewares as $middleware) {
            if (!$middleware instanceof Middleware) {
                throw new InvalidArgumentException('Class must implement'
                    . ' middleware interface.');
            }
        }

        $this->host = $host;
        $this->port = $port;
        $this->cert = $cert;
        $this->middlewares = array_values($middlewares);
    }

    /**
     * Serve a model.
     *
     * @param \Rubix\ML\Estimator $estimator
     * @throws \Rubix\Server\Exceptions\InvalidArgumentException
     */
    public function serve(Estimator $estimator) : void
    {
        if ($estimator instanceof Learner) {
            if (!$estimator->trained()) {
                throw new InvalidArgumentException('Cannot serve'
                    . ' an untrained learner.');
            }
        }

        $bus = CommandBus::boot($estimator, $this->logger);

        $this->router = $this->bootRouter($estimator, $bus);

        $loop = Loop::create();

        $socket = new Socket("{$this->host}:{$this->port}", $loop);

        if ($this->cert) {
            $socket = new SecureSocket($socket, $loop, [
                'local_cert' => $this->cert,
            ]);
        }

        $stack = $this->middlewares;

        $stack[] = [$this, 'addServerHeaders'];
        $stack[] = [$this->router, 'dispatch'];

        $server = new HTTPServer($loop, ...$stack);

        $server->listen($socket);

        if ($this->logger) {
            $this->logger->info('HTTP REST Server running at'
                . " {$this->host} on port {$this->port}");
        }

        $loop->run();
    }

    /**
     * Add the HTTP headers specific to this server.
     *
     * @internal
     *
     * @param \Psr\Http\Message\ServerRequestInterface $request
     * @param callable $next
     * @return \Psr\Http\Message\ResponseInterface
     */
    public function addServerHeaders(Request $request, callable $next) : Response
    {
        return $next($request)->withHeader('Server', self::SERVER_NAME);
    }

    /**
     * Boot the RESTful router.
     *
     * @param \Rubix\ML\Estimator $estimator
     * @param \Rubix\Server\Services\CommandBus $bus
     * @return \Rubix\Server\Services\Router $router
     */
    protected function bootRouter(Estimator $estimator, CommandBus $bus) : Router
    {
        $mapping = [
            '/model/predictions' => [
                'POST' => new PredictionsController($bus),
            ],
        ];

        if ($estimator instanceof Learner) {
            $mapping += [
                '/model/predictions/sample' => [
                    'POST' => new SamplePredictionsController($bus),
                ],
            ];
        }

        if ($estimator instanceof Probabilistic) {
            $mapping += [
                '/model/probabilities' => [
                    'POST' => new ProbabilitiesController($bus),
                ],
                '/model/probabilities/sample' => [
                   'POST' => new SampleProbabilitiesController($bus),
                ],
            ];
        }

        if ($estimator instanceof Ranking) {
            $mapping += [
                '/model/scores' => [
                    'POST' => new ScoresController($bus),
                ],
                '/model/scores/sample' => [
                    'POST' => new SampleScoresController($bus),
                ],
            ];
        }

        return new Router($mapping);
    }
}
