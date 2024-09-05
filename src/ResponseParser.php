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
     * @desc 编码返回数据
     * @param int $code
     * @param string $msg
     * @param array $data
     * @return string
     */
    public static function encode(int $code, string $msg, array $data = []): string
    {
        return MessagePack::pack([
            'code' => $code,
            'msg' => $msg,
            'data' => $data,
        ]);
    }
}
