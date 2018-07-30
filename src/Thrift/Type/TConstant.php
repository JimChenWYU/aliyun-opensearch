<?php

/*
 * This file is part of the jimchen/aliyun-opensearch.
 *
 * (c) JimChen <18219111672@163.com>
 *
 * This source file is subject to the MIT license that is bundled.
 */

namespace Thrift\Type;

/**
 * Base class for constant Management.
 */
abstract class TConstant
{
    /**
     * Don't instanciate this class.
     */
    protected function __construct()
    {
    }

    /**
     * Get a constant value.
     *
     * @param string $constant
     *
     * @return mixed
     */
    public static function get($constant)
    {
        if (is_null(static::$$constant)) {
            static::$$constant = call_user_func(
                    sprintf('static::init_%s', $constant)
                );
        }

        return static::$$constant;
    }
}
