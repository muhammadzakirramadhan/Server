<?php

namespace Rubix\Server\Tests\Http\Controllers;

use Rubix\Server\CommandBus;
use Rubix\Server\Http\Controllers\PredictionsController;
use Rubix\Server\Http\Controllers\Controller;
use Rubix\Server\Responses\PredictResponse;
use React\Http\Io\ServerRequest;
use Psr\Http\Message\ResponseInterface as Response;
use PHPUnit\Framework\TestCase;

class PredictionsControllerTest extends TestCase
{
    protected $controller;

    public function setUp()
    {
        $commandBus = $this->createMock(CommandBus::class);

        $commandBus->method('dispatch')
            ->willReturn(new PredictResponse([]));

        $this->controller = new PredictionsController($commandBus);
    }

    public function test_build_controller()
    {
        $this->assertInstanceOf(PredictionsController::class, $this->controller);
        $this->assertInstanceOf(Controller::class, $this->controller);
    }

    public function test_handle_request()
    {
        $request = new ServerRequest('POST', '/example', [], json_encode([
            'samples' => [
                ['The first step is to establish that something is possible, then probability will occur.'],
            ],
        ]) ?: null);

        $response = $this->controller->handle($request, ['model' => 'test']);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertEquals(200, $response->getStatusCode());
    }
}
