<?php

/*
 * This file is part of the jimchen/aliyun-opensearch.
 *
 * (c) JimChen <18219111672@163.com>
 *
 * This source file is subject to the MIT license that is bundled.
 */

namespace Thrift\Factory;

use Thrift\Protocol\TJSONProtocol;

/**
 * JSON Protocol Factory.
 */
class TJSONProtocolFactory implements TProtocolFactory
{
    public function __construct()
    {
    }

    public function getProtocol($trans)
    {
        return new TJSONProtocol($trans);
    }
}
