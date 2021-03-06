<?php

namespace Rubix\Server\GraphQL\Objects;

use Rubix\Server\Models\ServerInfo;
use Rubix\Server\Models\Versions;
use GraphQL\Type\Definition\Type;

class ServerInfoObject extends ObjectType
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
            'name' => 'ServerInfo',
            'description' => 'Information related to the status of the server.',
            'fields' => [
                'start' => [
                    'description' => 'The timestamp of when the server went up.',
                    'type' => Type::nonNull(Type::int()),
                    'resolve' => function (ServerInfo $info) : int {
                        return $info->start();
                    },
                ],
                'pid' => [
                    'description' => 'The process ID (PID) of the server.',
                    'type' => Type::int(),
                    'resolve' => function (ServerInfo $info) : ?int {
                        return $info->pid();
                    },
                ],
                'versions' => [
                    'type' => Type::nonNull(VersionsObject::singleton()),
                    'resolve' => function (ServerInfo $info) : Versions {
                        return $info->versions();
                    },
                ],
            ],
        ]);
    }
}
