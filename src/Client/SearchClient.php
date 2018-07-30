<?php

/*
 * This file is part of the jimchen/aliyun-opensearch.
 *
 * (c) JimChen <18219111672@163.com>
 *
 * This source file is subject to the MIT license that is bundled.
 */

namespace OpenSearch\Client;

use OpenSearch\Generated\Search\OpenSearchSearcherServiceIf;
use OpenSearch\Generated\Search\SearchParams;
use OpenSearch\Util\UrlParamsBuilder;

/**
 * 应用搜索操作类。
 *
 * 通过制定关键词、过滤条件搜索应用结果。
 */
class SearchClient implements OpenSearchSearcherServiceIf
{
    const SEARCH_API_PATH = '/apps/%s/search';

    private $openSearchClient;

    /**
     * 构造方法。
     *
     * @param \OpenSearch\Client\OpenSearchClient $openSearchClient 基础类，负责计算签名，和服务端进行交互和返回结果
     */
    public function __construct($openSearchClient)
    {
        $this->openSearchClient = $openSearchClient;
    }

    /**
     * 执行搜索操作。
     *
     * @param \OpenSearch\Generated\Search\SearchParams $searchParams 制定的搜索条件
     *
     * @return \OpenSearch\Generated\Common\OpenSearchResult OpenSearchResult类
     */
    public function execute(SearchParams $searchParams)
    {
        $path = self::getPath($searchParams);
        $builder = new UrlParamsBuilder($searchParams);

        return $this->openSearchClient->get($path, $builder->getHttpParams());
    }

    private static function getPath($searchParams)
    {
        $appNames = isset($searchParams->config->appNames) ? implode(',', $searchParams->config->appNames) : '';

        return sprintf(self::SEARCH_API_PATH, $appNames);
    }
}
