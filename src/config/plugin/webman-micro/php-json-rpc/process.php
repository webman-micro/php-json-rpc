<?php
return [
    // 自定义Text协议
    'text.protocol' => [
        'handler' => \WebmanMicro\PhpJsonRpc\Protocol\RpcTextProtocol::class,
        'listen' => 'text://0.0.0.0:' . config('etcd.discovery.server_port'),
        'count' => config('server.count'), // 跟api服务一样数量
    ]
];
