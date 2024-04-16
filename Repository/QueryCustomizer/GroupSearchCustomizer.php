<?php

/*
 * This file is part of CustomerGroupRank42
 *
 * Copyright(c) Akira Kurozumi <info@a-zumi.net>
 *
 * https://a-zumi.net
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Plugin\CustomerGroupRank42\Repository\QueryCustomizer;

use Doctrine\ORM\QueryBuilder;
use Eccube\Doctrine\Query\QueryCustomizer;
use Eccube\Util\StringUtil;
use Plugin\CustomerGroup42\Repository\QueryKey;

class GroupSearchCustomizer implements QueryCustomizer
{
    public function customize(QueryBuilder $builder, $params, $queryKey)
    {
        if (
            isset($params['buyTimes']) && isset($params['buyTotal'])
            && StringUtil::isNotBlank($params['buyTimes']) && StringUtil::isNotBlank($params['buyTotal'])
        ) {
            $builder
                ->where('g.buyTimes <= :buyTimes')
                ->orWhere('g.buyTotal <= :buyTotal')
                ->setParameter('buyTimes', $params['buyTimes'])
                ->setParameter('buyTotal', $params['buyTotal']);
        }
    }

    public function getQueryKey()
    {
        return QueryKey::GROUP_SEARCH;
    }
}
