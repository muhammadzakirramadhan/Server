<?php

namespace Rubix\Server;

use Rubix\ML\Estimator;
use Rubix\ML\Learner;
use Rubix\ML\Probabilistic;
use Rubix\ML\Ranking;
use Rubix\Server\Commands\Command;
use Rubix\Server\Commands\Predict;
use Rubix\Server\Commands\PredictSample;
use Rubix\Server\Commands\Proba;
use Rubix\Server\Commands\ProbaSample;
use Rubix\Server\Commands\Score;
use Rubix\Server\Commands\ScoreSample;
use Rubix\Server\Handlers\PredictHandler;
use Rubix\Server\Handlers\PredictSampleHandler;
use Rubix\Server\Handlers\ProbaHandler;
use Rubix\Server\Handlers\ProbaSampleHandler;
use Rubix\Server\Handlers\ScoreHandler;
use Rubix\Server\Handlers\ScoreSampleHandler;
use Rubix\Server\Responses\Response;
use Rubix\Server\Exceptions\HandlerNotFound;
use Rubix\Server\Exceptions\DomainException;
use Rubix\Server\Exceptions\InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Exception;

use function get_class;
use function call_user_func;

/**
 * Command Bus
 *
 * The command pattern is a behavioral design pattern in which a command
 * object is used to encapsulate all information needed to perform an
 * action. The command bus is responsible for dispatching the commands to
 * their appropriate handlers.
 *
 * @category    Machine Learning
 * @package     Rubix/Server
 * @author      Andrew DalPino
 */
class CommandBus
{
    /**
     * The mapping of commands to their handlers.
     *
     * @var callable[]
     */
    protected $mapping;

    /**
     * A PSR-3 logger instance.
     *
     * @var \Psr\Log\LoggerInterface|null
     */
    protected $logger;

    /**
     * Boot the command bus.
     *
     * @param \Rubix\ML\Estimator $estimator
     * @param \Psr\Log\LoggerInterface|null $logger
     * @return self
     */
    public static function boot(Estimator $estimator, ?LoggerInterface $logger = null) : self
    {
        $mapping = [];

        if ($estimator instanceof Estimator) {
            $mapping += [
                Predict::class => new PredictHandler($estimator),
            ];
        }

        if ($estimator instanceof Learner) {
            $mapping += [
                PredictSample::class => new PredictSampleHandler($estimator),
            ];
        }

        if ($estimator instanceof Probabilistic) {
            $mapping += [
                Proba::class => new ProbaHandler($estimator),
                ProbaSample::class => new ProbaSampleHandler($estimator),
            ];
        }

        if ($estimator instanceof Ranking) {
            $mapping += [
                Score::class => new ScoreHandler($estimator),
                ScoreSample::class => new ScoreSampleHandler($estimator),
            ];
        }

        return new self($mapping, $logger);
    }

    /**
     * @param callable[] $mapping
     * @param \Psr\Log\LoggerInterface|null $logger
     * @throws \Rubix\Server\Exceptions\InvalidArgumentException
     */
    public function __construct(array $mapping, ?LoggerInterface $logger = null)
    {
        foreach ($mapping as $command => $handler) {
            if (!class_exists($command)) {
                throw new InvalidArgumentException("$command does not exist.");
            }

            if (!is_callable($handler)) {
                throw new InvalidArgumentException('Handler must be callable.');
            }
        }

        $this->mapping = $mapping;
        $this->logger = $logger;
    }

    /**
     * Dispatch the command to a handler.
     *
     * @param \Rubix\Server\Commands\Command $command
     * @throws \Rubix\Server\Exceptions\HandlerNotFound
     * @throws \Rubix\Server\Exceptions\DomainException
     * @return \Rubix\Server\Responses\Response
     */
    public function dispatch(Command $command) : Response
    {
        $class = get_class($command);

        if (!isset($this->mapping[$class])) {
            throw new HandlerNotFound($command);
        }

        $handler = $this->mapping[$class];

        try {
            return call_user_func($handler, $command);
        } catch (Exception $exception) {
            $exception = new DomainException($exception);

            if ($this->logger) {
                $this->logger->error((string) $exception);
            }

            throw $exception;
        }
    }
}
