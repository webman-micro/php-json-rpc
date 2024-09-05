<?php
declare(strict_types=1);

namespace WebmanMicro\PhpJsonRpc\Exception;


use WebmanMicro\PhpJsonRpc\Error;

class RpcResponseException extends \Exception
{
    protected $error;

    public function __construct(Error $error)
    {
        parent::__construct($error->getMessage(), $error->getCode());
        $this->error = $error;
    }

    public function getError(): Error
    {
        return $this->error;
    }
}
