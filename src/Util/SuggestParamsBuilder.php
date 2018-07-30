<?php

/*
 * This file is part of the jimchen/aliyun-opensearch.
 *
 * (c) JimChen <18219111672@163.com>
 *
 * This source file is subject to the MIT license that is bundled.
 */

namespace OpenSearch\Util;

use OpenSearch\Generated\Search\Config;
use OpenSearch\Generated\Search\SearchParams;
use OpenSearch\Generated\Search\Suggest;

class SuggestParamsBuilder
{
    public function __construct()
    {
    }

    /**
     * 创建一个下拉提示的搜索请求。
     *
     * @param string $appName     指定应用的名称
     * @param string $suggestName 指定下拉提示的名称
     * @param string $query       指定要搜索的关键词
     * @param int    $hits        指定要返回的词条个数
     *
     * @return \OpenSearch\Generated\Search\SearchParams
     */
    public static function build($appName, $suggestName, $query, $hits)
    {
        $config = new Config(['hits' => (int) $hits, 'appNames' => [$appName]]);
        $suggest = new Suggest(['suggestName' => $suggestName]);

        return new SearchParams(['config' => $config, 'query' => $query, 'suggest' => $suggest]);
    }

    /**
     * 根据SearchParams生成下拉提示搜索的参数。
     *
     * @param \OpenSearch\Generated\Search\SearchParams $searchParams searchParams
     *
     * @return array
     */
    public static function getQueryParams($searchParams)
    {
        $query = $searchParams->query;
        $hits = $searchParams->config->hits;

        return ['query' => $query, 'hit' => $hits];
    }
}
