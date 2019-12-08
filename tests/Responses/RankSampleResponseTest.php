<?php

namespace Rubix\Server\Tests\Responses;

use Rubix\Server\Responses\Response;
use Rubix\Server\Responses\RankSampleResponse;
use PHPUnit\Framework\TestCase;

class RankSampleResponseTest extends TestCase
{
    protected const EXPECTED_SCORE = 4.5;

    /**
     * @var \Rubix\Server\Responses\RankSampleResponse
     */
    protected $response;

    public function setUp() : void
    {
        $this->response = new RankSampleResponse(self::EXPECTED_SCORE);
    }

    public function test_build_response() : void
    {
        $this->assertInstanceOf(RankSampleResponse::class, $this->response);
        $this->assertInstanceOf(Response::class, $this->response);
    }

    public function test_as_array() : void
    {
        $expected = [
            'score' => self::EXPECTED_SCORE,
        ];
        
        $payload = $this->response->asArray();

        $this->assertInternalType('array', $payload);
        $this->assertEquals($expected, $payload);
    }
}
