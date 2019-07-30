<?php

/*
 * This file is part of the jimchen/aliyun-opensearch.
 *
 * (c) JimChen <18219111672@163.com>
 *
 * This source file is subject to the MIT license that is bundled.
 */

namespace OpenSearch\Util;

use OpenSearch\Generated\Search\Constant;
use OpenSearch\Generated\Search\Order;
use OpenSearch\Generated\Search\searchFormat;

class ClauseParamsBuilder
{
    const CONFIG_KEY = 'config';
    const QUERY_KEY = 'query';
    const SORT_KEY = 'sort';
    const DISTINCT_KEY = 'distinct';
    const AGGREGATE_KEY = 'aggregate';
    const FILTER_KEY = 'filter';
    const KVPAIRS = 'kvpairs';

    const CLAUSE_SEPARATOR = '&&';
    const KV_SEPARATOR = '=';
    const CLAUSE_CONFIG_SEPARATOR = ',';
    const CLAUSE_CONFIG_KV_SEPARATOR = ':';

    const CLAUSE_SORT_SEPARATOR = ';';

    const CLAUSE_DISTINCT_KV_SEPARATOR = ':';
    const CLAUSE_DISTINCT_SEPARATOR = ';';
    const CLAUSE_DISTINCT_SUB_SEPARATOR = ',';

    const CLAUSE_AGGREGATE_KV_SEPARATOR = ':';
    const CLAUSE_AGGREGATE_SEPARATOR = ';';
    const CLAUSE_AGGREGATE_SUB_SEPARATOR = ',';

    const CONFIG_CLAUSE_START = 'CONFIG_CLAUSE_START';
    const CONFIG_CLAUSE_HIT = 'CONFIG_CLAUSE_HIT';
    const CONFIG_CLAUSE_RERANK_SIZE = 'CONFIG_CLAUSE_RERANK_SIZE';
    const CONFIG_CLAUSE_FORMAT = 'CONFIG_CLAUSE_FORMAT';

    const DISTINCT_CLAUSE_DIST_KEY = 'DISTINCT_CLAUSE_DIST_KEY';
    const DISTINCT_CLAUSE_DIST_COUNT = 'DISTINCT_CLAUSE_DIST_COUNT';
    const DISTINCT_CLAUSE_DIST_TIMES = 'DISTINCT_CLAUSE_DIST_TIMES';
    const DISTINCT_CLAUSE_RESERVED = 'DISTINCT_CLAUSE_RESERVED';
    const DISTINCT_CLAUSE_DIST_FILTER = 'DISTINCT_CLAUSE_DIST_FILTER';
    const DISTINCT_CLAUSE_UPDATE_TOTAL_HIT = 'DISTINCT_CLAUSE_UPDATE_TOTAL_HIT';
    const DISTINCT_CLAUSE_GRADE = 'DISTINCT_CLAUSE_GRADE';

    const AGGREGATE_CLAUSE_GROUP_KEY = 'AGGREGATE_CLAUSE_GROUP_KEY';
    const AGGREGATE_CLAUSE_AGG_FUN = 'AGGREGATE_CLAUSE_AGG_FUN';
    const AGGREGATE_CLAUSE_RANGE = 'AGGREGATE_CLAUSE_RANGE';
    const AGGREGATE_CLAUSE_MAX_GROUP = 'AGGREGATE_CLAUSE_MAX_GROUP';
    const AGGREGATE_CLAUSE_AGG_FILTER = 'AGGREGATE_CLAUSE_AGG_FILTER';
    const AGGREGATE_CLAUSE_AGG_SAMPLER_THRESHOLD = 'AGGREGATE_CLAUSE_AGG_SAMPLER_THRESHOLD';
    const AGGREGATE_CLAUSE_AGG_SAMPLER_STEP = 'AGGREGATE_CLAUSE_AGG_SAMPLER_STEP';

    private $params;

    private $clauses = [];

    public function __construct($params)
    {
        $this->params = $params;
    }

    private function buildConfigClause()
    {
        $config = [];
        if (isset($this->params->config->start)) {
            $config[] = Constant::get(self::CONFIG_CLAUSE_START).
                self::CLAUSE_CONFIG_KV_SEPARATOR.$this->params->config->start;
        }

        if (isset($this->params->config->hits)) {
            $config[] = Constant::get(self::CONFIG_CLAUSE_HIT).
                self::CLAUSE_CONFIG_KV_SEPARATOR.$this->params->config->hits;
        }

        if (isset($this->params->config->searchFormat)) {
            $format = $this->params->config->searchFormat;
            $config[] = Constant::get(self::CONFIG_CLAUSE_FORMAT).
                self::CLAUSE_CONFIG_KV_SEPARATOR.strtolower(searchFormat::$__names[$format]);
        }

        if (isset($this->params->rank->reRankSize)) {
            $config[] = Constant::get(self::CONFIG_CLAUSE_RERANK_SIZE).
                self::CLAUSE_CONFIG_KV_SEPARATOR.$this->params->rank->reRankSize;
        }

        if (isset($this->params->config->customConfig)) {
            foreach ($this->params->config->customConfig as $k => $v) {
                $config[] = $k.self::CLAUSE_CONFIG_KV_SEPARATOR.$v;
            }
        }

        $this->clauses[self::CONFIG_KEY] = implode(self::CLAUSE_CONFIG_SEPARATOR, $config);
    }

    private function buildQueryClause()
    {
        if (null !== $this->params->query) {
            $this->clauses[self::QUERY_KEY] = $this->params->query;
        }
    }

    private function buildSortClause()
    {
        $sorts = [];
        if (isset($this->params->sort->sortFields)) {
            foreach ($this->params->sort->sortFields as $sortField) {
                $order = $sortField->order;
                $orderString = Order::$__names[$order];
                $sorts[] = Constant::get('SORT_CLAUSE_'.$orderString).$sortField->field;
            }

            $this->clauses[self::SORT_KEY] = implode(self::CLAUSE_SORT_SEPARATOR, $sorts);
        }
    }

    private function buildFilterClause()
    {
        if (isset($this->params->filter)) {
            $this->clauses[self::FILTER_KEY] = $this->params->filter;
        }
    }

    private function buildDistinctClause()
    {
        $distincts = [];
        if (isset($this->params->distincts)) {
            $keys = [
                'key' => self::DISTINCT_CLAUSE_DIST_KEY,
                'distCount' => self::DISTINCT_CLAUSE_DIST_COUNT,
                'distTimes' => self::DISTINCT_CLAUSE_DIST_TIMES,
                'reserved' => self::DISTINCT_CLAUSE_RESERVED,
                'distFilter' => self::DISTINCT_CLAUSE_DIST_FILTER,
                'updateTotalHit' => self::DISTINCT_CLAUSE_UPDATE_TOTAL_HIT,
                'grade' => self::DISTINCT_CLAUSE_GRADE,
            ];
            foreach ($this->params->distincts as $distinct) {
                if (!isset($distinct->key)) {
                    continue;
                }

                $dist = [];
                foreach ($keys as $k => $v) {
                    if (isset($distinct->$k)) {
                        $dist[] = Constant::get($v).self::CLAUSE_AGGREGATE_KV_SEPARATOR.$distinct->$k;
                    }
                }

                $distincts[] = implode(self::CLAUSE_DISTINCT_SUB_SEPARATOR, $dist);
            }

            $this->clauses[self::DISTINCT_KEY] = implode(self::CLAUSE_DISTINCT_SEPARATOR, $distincts);
        }
    }

    private function buildAggregateClause()
    {
        $aggregates = [];
        if (isset($this->params->aggregates)) {
            $keys = [
                'groupKey' => self::AGGREGATE_CLAUSE_GROUP_KEY,
                'aggFun' => self::AGGREGATE_CLAUSE_AGG_FUN,
                'range' => self::AGGREGATE_CLAUSE_RANGE,
                'maxGroup' => self::AGGREGATE_CLAUSE_MAX_GROUP,
                'aggFilter' => self::AGGREGATE_CLAUSE_AGG_FILTER,
                'aggSamplerThresHold' => self::AGGREGATE_CLAUSE_AGG_SAMPLER_THRESHOLD,
                'aggSamplerStep' => self::AGGREGATE_CLAUSE_AGG_SAMPLER_STEP,
            ];

            foreach ($this->params->aggregates as $aggregate) {
                if (!isset($aggregate->groupKey) || !isset($aggregate->aggFun)) {
                    continue;
                }

                $agg = [];
                foreach ($keys as $k => $v) {
                    if (isset($aggregate->$k)) {
                        $agg[] = Constant::get($v).self::CLAUSE_AGGREGATE_KV_SEPARATOR.$aggregate->$k;
                    }
                }

                $aggregates[] = implode(self::CLAUSE_AGGREGATE_SUB_SEPARATOR, $agg);
            }

            $this->clauses[self::AGGREGATE_KEY] = implode(self::CLAUSE_AGGREGATE_SEPARATOR, $aggregates);
        }
    }

    private function buildKVPairsClause()
    {
        if (isset($this->params->config->kvpairs)) {
            $this->clauses[self::KVPAIRS] = $this->params->config->kvpairs;
        }
    }

    public function getClausesString()
    {
        $this->buildConfigClause();
        $this->buildQueryClause();
        $this->buildSortClause();
        $this->buildFilterClause();
        $this->buildDistinctClause();
        $this->buildAggregateClause();
        $this->buildKVPairsClause();

        $clauses = [];
        foreach ($this->clauses as $clauseKey => $value) {
            $clauses[] = $clauseKey.self::KV_SEPARATOR.$value;
        }

        return implode(self::CLAUSE_SEPARATOR, $clauses);
    }
}
