<?php

namespace Rubix\Server\Tests\Http\Controllers;

use Rubix\Server\CommandBus;
use Rubix\Server\Http\Controllers\SamplePredictionController;
use Rubix\Server\Http\Controllers\Controller;
use Rubix\Server\Responses\PredictSampleResponse;
use React\Http\Io\ServerRequest;
use Psr\Http\Message\ResponseInterface as Response;
use PHPUnit\Framework\TestCase;

class SamplePredictionControllerTest extends TestCase
{
    /**
     * @var \Rubix\Server\Http\Controllers\SamplePredictionController
     */
    protected $controller;

    public function setUp() : void
    {
        $commandBus = $this->createMock(CommandBus::class);

        $commandBus->method('dispatch')
            ->willReturn(new PredictSampleResponse([]));

        $this->controller = new SamplePredictionController($commandBus);
    }

    public function test_build_controller() : void
    {
        $this->assertInstanceOf(SamplePredictionController::class, $this->controller);
        $this->assertInstanceOf(Controller::class, $this->controller);
    }

    public function test_handle_request() : void
    {
        $request = new ServerRequest('POST', '/example', [], json_encode([
            'sample' => ['The first step is to establish that something is possible, then probability will occur.'],
        ]) ?: null);

        $response = $this->controller->handle($request);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
    }
}
