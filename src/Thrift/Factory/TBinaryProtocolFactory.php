<?php

/*
 * This file is part of the jimchen/aliyun-opensearch.
 *
 * (c) JimChen <18219111672@163.com>
 *
 * This source file is subject to the MIT license that is bundled.
 */

namespace Thrift\Factory;

use Thrift\Protocol\TBinaryProtocol;

/**
 * Binary Protocol Factory.
 */
class TBinaryProtocolFactory implements TProtocolFactory
{
    private $strictRead_ = false;
    private $strictWrite_ = false;

    public function __construct($strictRead = false, $strictWrite = false)
    {
        $this->strictRead_ = $strictRead;
        $this->strictWrite_ = $strictWrite;
    }

    public function getProtocol($trans)
    {
        return new TBinaryProtocol($trans, $this->strictRead_, $this->strictWrite_);
    }
}
