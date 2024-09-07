<?php
declare(strict_types=1);

namespace WebmanMicro\PhpJsonRpc;

use WebmanMicro\PhpJsonRpc\Exception\RpcResponseException;
use WebmanMicro\PhpJsonRpc\Exception\RpcUnexpectedValueException;
use MessagePack\MessagePack;
use WebmanMicro\PhpServiceDiscovery\Etcd\Discovery;
use WebmanMicro\PhpBreaker\BreakerFactory;

class Client
{
    /**
     * @var string
     */
    private string $serverName;

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
     * @param string $callFunc
     * @param array $arg
     * @return mixed
     */
    public function request(string $callFunc, array $arg)
    {
        try {
            // 根据服务名获取服务地址
            [$class, $method] = explode('/', $callFunc);

            if (BreakerFactory::isAvailable($this->serverName)) {
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
                    // 请求失败设置
                    BreakerFactory::failure($this->serverName);
                    throw new RpcResponseException(Error::make(408, 'rpc request timeout'));
                }

                fclose($resource);
                $res = MessagePack::unpack($result);

                if (!empty($res['code']) && $res['code'] !== 200) {
                    // 请求失败设置
                    BreakerFactory::failure($this->serverName);
                    throw new RpcResponseException(Error::make($res['code'], $res['msg']));
                }

                // 请求成功设置
                BreakerFactory::success($this->serverName);
                return $res;
            } else {
                throw new RpcResponseException(Error::make(500, $this->serverName . ' service is unavailable'));
            }

        } catch (\Throwable $throwable) {
            // 请求失败设置
            BreakerFactory::failure($this->serverName);
            throw new RpcUnexpectedValueException('rpc request failed: ' . $throwable->getMessage());
        }
    }
}
