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
 * Protocol module. Contains all the types and definitions needed to implement
 * a protocol encoder/decoder.
 */

/**
 * Protocol exceptions.
 */
class TProtocolException extends TException
{
    const UNKNOWN = 0;
    const INVALID_DATA = 1;
    const NEGATIVE_SIZE = 2;
    const SIZE_LIMIT = 3;
    const BAD_VERSION = 4;
    const NOT_IMPLEMENTED = 5;
    const DEPTH_LIMIT = 6;

    public function __construct($message = null, $code = 0)
    {
        parent::__construct($message, $code);
    }
}
