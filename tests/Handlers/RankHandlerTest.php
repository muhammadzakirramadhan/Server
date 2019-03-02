<?php

namespace Rubix\Server\Tests\Handlers;

use Rubix\Server\Commands\Rank;
use Rubix\Server\Handlers\RankHandler;
use Rubix\Server\Handlers\Handler;
use Rubix\Server\Responses\RankResponse;
use Rubix\ML\AnomalyDetectors\IsolationForest;
use PHPUnit\Framework\TestCase;

class RankHandlerTest extends TestCase
{
    protected const SAMPLES = [
        ['nice', 'rough', 'loner'],
        ['mean', 'furry', 'loner'],
        ['nice', 'rough', 'friendly'],
    ];
    
    protected const EXPECTED_SCORES = [
        6, 4, 10,
    ];

    protected $command;
    
    protected $handler;

    public function setUp()
    {
        $estimator = $this->createMock(IsolationForest::class);

        $estimator->method('rank')
            ->willReturn(self::EXPECTED_SCORES);

        $this->command = new Rank(self::SAMPLES);

        $this->handler = new RankHandler($estimator);
    }

    public function test_build_handler()
    {
        $this->assertInstanceOf(RankHandler::class, $this->handler);
        $this->assertInstanceOf(Handler::class, $this->handler);
    }

    public function test_handle_command()
    {
        $response = $this->handler->handle($this->command);

        $this->assertInstanceOf(RankResponse::class, $response);
        $this->assertEquals(self::EXPECTED_SCORES, $response->scores());
    }
}