<?php

namespace Rubix\Server\Helpers;

use Rubix\Server\Exceptions\JSONException;

/**
 * JSON
 *
 * A helper class providing functions relating to JSON (Javascript Object Notation).
 *
 * @category    Machine Learning
 * @package     Rubix/ML
 * @author      Chris Simpson
 */
class JSON
{
    /**
     * The default maximum stack depth.
     *
     * @var int
     */
    protected const DEFAULT_DEPTH = 512;

    /**
     * @var int
     */
    protected const DEFAULT_OPTIONS = 0;

    /**
     * Serialize to JSON.
     *
     * @param mixed $value
     * @param int $options
     * @param int $depth
     * @throws \Rubix\Server\Exceptions\JSONException
     * @return string
     */
    public static function encode($value, int $options = self::DEFAULT_OPTIONS, int $depth = self::DEFAULT_DEPTH) : string
    {
        $data = json_encode($value, $options, $depth) ?: '';

        $code = json_last_error();

        if ($code !== JSON_ERROR_NONE) {
            throw new JSONException($code);
        }

        return $data;
    }

    /**
     * Deserialize from JSON.
     *
     * @param string $data
     * @param int $options
     * @param int $depth
     * @throws \Rubix\Server\Exceptions\JSONException
     * @return mixed[]
     */
    public static function decode(string $data, int $options = self::DEFAULT_OPTIONS, int $depth = self::DEFAULT_DEPTH) : array
    {
        $value = json_decode($data, true, $depth, $options);

        $code = json_last_error();

        if ($code !== JSON_ERROR_NONE) {
            throw new JSONException($code);
        }

        return $value;
    }
}
