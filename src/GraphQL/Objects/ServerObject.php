<?php

namespace Rubix\Server\GraphQL\Objects;

use Rubix\Server\Models\Server;
use Rubix\Server\Models\HTTPStats;
use Rubix\Server\Models\Memory;
use Rubix\Server\Models\ServerInfo;
use Rubix\Server\Models\ServerSettings;
use GraphQL\Type\Definition\Type;

class ServerObject extends ObjectType
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
            'name' => 'Server',
            'description' => 'The server.',
            'fields' => [
                'httpStats' => [
                    'type' => Type::nonNull(HTTPStatsObject::singleton()),
                    'resolve' => function (Server $server) : HTTPStats {
                        return $server->httpStats();
                    },
                ],
                'memory' => [
                    'type' => Type::nonNull(MemoryObject::singleton()),
                    'resolve' => function (Server $server) : Memory {
                        return $server->memory();
                    },
                ],
                'info' => [
                    'type' => Type::nonNull(ServerInfoObject::singleton()),
                    'resolve' => function (Server $server) : ServerInfo {
                        return $server->info();
                    },
                ],
                'settings' => [
                    'type' => Type::nonNull(ServerSettingsObject::singleton()),
                    'resolve' => function (Server $server) : ServerSettings {
                        return $server->settings();
                    },
                ],
            ],
        ]);
    }
}
