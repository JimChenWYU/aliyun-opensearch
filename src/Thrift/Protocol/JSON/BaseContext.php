<?php

/*
 * This file is part of the jimchen/aliyun-opensearch.
 *
 * (c) JimChen <18219111672@163.com>
 *
 * This source file is subject to the MIT license that is bundled.
 */

namespace Thrift\Protocol\JSON;

class BaseContext
{
    public function escapeNum()
    {
        return false;
    }

    public function write()
    {
    }

    public function read()
    {
    }
}
