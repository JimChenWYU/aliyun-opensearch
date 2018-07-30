<?php

/*
 * This file is part of the jimchen/aliyun-opensearch.
 *
 * (c) JimChen <18219111672@163.com>
 *
 * This source file is subject to the MIT license that is bundled.
 */

namespace Thrift\Protocol\SimpleJSON;

class MapContext extends StructContext
{
    protected $isKey = true;
    private $p_;

    public function __construct($p)
    {
        parent::__construct($p);
    }

    public function write()
    {
        parent::write();
        $this->isKey = !$this->isKey;
    }

    public function isMapKey()
    {
        // we want to coerce map keys to json strings regardless
        // of their type
        return $this->isKey;
    }
}
