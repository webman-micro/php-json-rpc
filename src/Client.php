<?php
declare(strict_types=1);

namespace WebmanMicro\PhpJsonRpc;

use WebmanMicro\PhpJsonRpc\Exception\RpcResponseException;
use WebmanMicro\PhpJsonRpc\Exception\RpcUnexpectedValueException;
use MessagePack\MessagePack;
use WebmanMicro\PhpServiceDiscovery\Etcd\Discovery;

class Client
{
    /**
     * @var string
     */
    private $serverName;

    /**
     * Client constructor.
     */
    public function __construct(string $serverName)
    {
        $this->serverName = strtolower($serverName);
    }

    /**
     * 获取对方服务地址
     * @param string $serverName
     * @return string
     */
    private function setServerHost(string $serverName = ''): string
    {
        $host = Discovery::instance()->getServerConfigByName($serverName);
        if (!empty($host)) {
            // 服务存在
            return 'tcp://' . $host;
        } else {
            throw new RpcUnexpectedValueException($serverName . ' server not exit');
        }
    }

    /**
     * 请求数据
     * @param string $method
     * @param array $arg
     * @return mixed
     */
    public function request(string $method, array $arg)
    {
        try {
            // 根据服务名获取服务地址
            [$class, $method] = explode('/', $method);

            $resource = stream_socket_client($this->setServerHost($this->serverName), $errno, $errorMessage);
            if (false === $resource) {
                throw new RpcUnexpectedValueException('rpc failed to connect: ' . $errorMessage);
            }

            // 如果param数组里面存在timeout参数，就设置超时时间
            $timeout = $param['timeout'] ?? 0;
            if ($timeout > 0) {
                stream_set_timeout($resource, $timeout);
            }

            $param = [
                'class' => $class,
                'method' => $method,
                'args' => $arg
            ];
            fwrite($resource, MessagePack::pack($param) . "\n");
            $result = fgets($resource, 10240000);

            // 检查是否超时,并报超时异常
            $info = stream_get_meta_data($resource);
            if ($info['timed_out']) {
                throw new RpcResponseException(Error::make(408, 'rpc request timeout'));
            }

            fclose($resource);
            $res = MessagePack::unpack($result);

            if (!empty($res['code']) && $res['code'] !== 200) {
                throw new RpcResponseException(Error::make($res['code'], $res['msg']));
            }

            return $res;

        } catch (\Throwable $throwable) {
            throw new RpcUnexpectedValueException('rpc request failed: ' . $throwable->getMessage());
        }
    }
}
