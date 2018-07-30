<?php

/*
 * This file is part of the jimchen/aliyun-opensearch.
 *
 * (c) JimChen <18219111672@163.com>
 *
 * This source file is subject to the MIT license that is bundled.
 */

namespace Thrift\StringFunc;

interface TStringFunc
{
    public function substr($str, $start, $length = null);

    public function strlen($str);
}
