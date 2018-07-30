<?php

/*
 * This file is part of the jimchen/aliyun-opensearch.
 *
 * (c) JimChen <18219111672@163.com>
 *
 * This source file is subject to the MIT license that is bundled.
 */

namespace Thrift\Protocol\SimpleJSON;

use Thrift\Exception\TException;

class CollectionMapKeyException extends TException
{
    public function __construct($message)
    {
        parent::__construct($message);
    }
}
