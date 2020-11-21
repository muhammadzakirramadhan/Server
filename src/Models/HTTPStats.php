<?php

namespace Rubix\Server\Models;

use Rubix\Server\Services\SSEChannel;
use Psr\Http\Message\ResponseInterface;

class HTTPStats implements Model
{
    /**
     * The server-sent events emitter.
     *
     * @var \Rubix\Server\Services\SSEChannel
     */
    protected $channel;

    /**
     * The number of successful requests handled by the server.
     *
     * @var int
     */
    protected $numSuccessful = 0;

    /**
     * The number of rejected requests.
     *
     * @var int
     */
    protected $numRejected = 0;

    /**
     * The number of failed requests.
     *
     * @var int
     */
    protected $numFailed = 0;

    /**
     * @param \Rubix\Server\Services\SSEChannel $channel
     */
    public function __construct(SSEChannel $channel)
    {
        $this->channel = $channel;
    }

    /**
     * Increment the response counter for a given response.
     *
     * @param \Psr\Http\Message\ResponseInterface $response
     */
    public function incrementResponseCount(ResponseInterface $response) : void
    {
        $code = $response->getStatusCode();

        if ($code >= 100 and $code < 400) {
            ++$this->numSuccessful;

            $this->channel->emit('http-successful-incremented');
        } elseif ($code >= 400 and $code < 500) {
            ++$this->numRejected;

            $this->channel->emit('http-rejected-incremented');
        } elseif ($code >= 500) {
            ++$this->numFailed;

            $this->channel->emit('http-failed-incremented');
        }
    }

    /**
     * Return the number of successful requests handled by the server.
     *
     * @return int
     */
    public function numSuccessful() : int
    {
        return $this->numSuccessful;
    }

    /**
     * Return the number of rejected requests.
     *
     * @return int
     */
    public function numRejected() : int
    {
        return $this->numRejected;
    }

    /**
     * Return the number of failed requests.
     *
     * @return int
     */
    public function numFailed() : int
    {
        return $this->numFailed;
    }

    /**
     * Return the model as an associative array.
     *
     * @return mixed[]
     */
    public function asArray() : array
    {
        return [
            'successful' => $this->numSuccessful,
            'rejected' => $this->numRejected,
            'failed' => $this->numFailed,
        ];
    }
}
