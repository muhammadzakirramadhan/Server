<?php

namespace Rubix\Server\GraphQL\Objects;

use Rubix\Server\Models\Versions;
use GraphQL\Type\Definition\Type;

class VersionsObject extends ObjectType
{
    /**
     * The singleton instance of the object type.
     *
     * @var self|null
     */
    protected static $instance;

    /**
     * @return self
     */
    public static function singleton() : self
    {
        return self::$instance ?? self::$instance = new self([
            'name' => 'Versions',
            'description' => 'Version numbers.',
            'fields' => [
                'server' => [
                    'description' => 'The version number of the Server library.',
                    'type' => Type::nonNull(Type::string()),
                    'resolve' => function (Versions $versions) : string {
                        return $versions->server();
                    },
                ],
                'ml' => [
                    'description' => 'The version number of the ML library.',
                    'type' => Type::nonNull(Type::string()),
                    'resolve' => function (Versions $versions) : string {
                        return $versions->ml();
                    },
                ],
                'php' => [
                    'description' => 'The version number of the PHP runtime.',
                    'type' => Type::nonNull(Type::string()),
                    'resolve' => function (Versions $versions) : string {
                        return $versions->php();
                    },
                ],
            ],
        ]);
    }
}
