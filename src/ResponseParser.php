<?php
declare(strict_types=1);

namespace WebmanMicro\PhpJsonRpc;


use Workerman\Connection\TcpConnection;
use MessagePack\MessagePack;

class ResponseParser
{
    /**
     * rpc version
     */
    const VERSION = '1.0';

    const DELIMITER = "@";

    /**
     * Parser error
     */
    const PARSER_ERROR = 32700;

    /**
     * Invalid Request
     */
    const INVALID_REQUEST = 32600;

    /**
     * Method not found
     */
    const METHOD_NOT_FOUND = 32601;

    /**
     * Invalid params
     */
    const INVALID_PARAMS = 32602;

    /**
     * Internal error
     */
    const INTERNAL_ERROR = 32603;

    /**
     * 强制把整形转成字符串
     * @param $args
     * @return void
     */
    protected static function convertIntToString(&$args)
    {
        foreach ($args as &$arg) {
            if (is_array($arg)) {
                self::convertIntToString($arg);
            } else {
                if (is_numeric($arg)) {
                    $arg = (string)$arg;
                }
            }
        }
    }

    /**
     * @desc 编码返回数据
     * @param int $code
     * @param string $msg
     * @param array $data
     * @return string
     */
    public static function encode(int $code, string $msg, array $data = []): string
    {
        $responseData = [
            'code' => $code,
            'msg' => $msg,
            'data' => $data,
        ];

        self::convertIntToString($responseData);
        return MessagePack::pack($responseData);
    }
}
