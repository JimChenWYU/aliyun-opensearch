<?php

/*
 * This file is part of the jimchen/aliyun-opensearch.
 *
 * (c) JimChen <18219111672@163.com>
 *
 * This source file is subject to the MIT license that is bundled.
 */

namespace Thrift\StringFunc;

class Core implements TStringFunc
{
    public function substr($str, $start, $length = null)
    {
        // specifying a null $length would return an empty string
        if (null === $length) {
            return substr($str, $start);
        }

        return substr($str, $start, $length);
    }

    public function strlen($str)
    {
        return strlen($str);
    }
}
