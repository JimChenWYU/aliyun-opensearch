<?php

/*
 * This file is part of the jimchen/aliyun-opensearch.
 *
 * (c) JimChen <18219111672@163.com>
 *
 * This source file is subject to the MIT license that is bundled.
 */

namespace Thrift\Exception;

/**
 * Transport exceptions.
 */
class TTransportException extends TException
{
    const UNKNOWN = 0;
    const NOT_OPEN = 1;
    const ALREADY_OPEN = 2;
    const TIMED_OUT = 3;
    const END_OF_FILE = 4;

    public function __construct($message = null, $code = 0)
    {
        parent::__construct($message, $code);
    }
}
