<?php
declare(strict_types=1);

namespace WebmanMicro\PhpJsonRpc\Protocol;

use MessagePack\MessagePack;
use Throwable;
use WebmanMicro\PhpJsonRpc\ResponseParser;
use Workerman\Connection\TcpConnection;

class RpcTextProtocol
{
    /**
     * @param TcpConnection $connection
     * @param string $string
     * @return bool|null
     */
    public function onMessage(TcpConnection $connection, string $string): ?bool
    {
        try {
            static $instances = [];
            $data = MessagePack::unpack($string);
            $config = config('plugin.webman-micro.php-json-rpc.app');
            $class = $config['server']['namespace'] . $data['class'];
            if (!class_exists($class)) {
                return $connection->send(ResponseParser::encode(404, sprintf('%s Class is not exist!', $data['class'])));
            }

            $method = $data['method'];
            if (!method_exists($class, (string)$method)) {
                return $connection->send(ResponseParser::encode(404, sprintf('%s method is not exist!', $method)));
            }

            $args = $data['args'] ?? [];
            if (!isset($instances[$class])) {
                $instances[$class] = new $class();
            }
            return $connection->send(call_user_func_array([$instances[$class], $method], $args));
        } catch (Throwable $th) {
            return $connection->send(ResponseParser::encode(500, $th->getMessage() . '| file:' . $th->getFile() . '| line:' . $th->getLine()));
        }
    }
}
