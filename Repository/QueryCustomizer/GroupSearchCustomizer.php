<?php

/*
 * This file is part of CustomerGroupRank
 *
 * Copyright(c) Akira Kurozumi <info@a-zumi.net>
 *
 * https://a-zumi.net
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Plugin\CustomerGroupRank44\Repository\QueryCustomizer;

use Doctrine\ORM\QueryBuilder;
use Eccube\Doctrine\Query\QueryCustomizer;
use Eccube\Util\StringUtil;
use Plugin\CustomerGroup44\Repository\QueryKey;

class GroupSearchCustomizer implements QueryCustomizer
{
    public function customize(QueryBuilder $builder, $params, $queryKey): void
    {
        if (
            isset($params['buyTimes'], $params['buyTotal'])
            && StringUtil::isNotBlank($params['buyTimes'])
            && StringUtil::isNotBlank($params['buyTotal'])
        ) {
            $builder
                ->andWhere(
                    $builder->expr()->orX(
                        'g.buyTimes <= :buyTimes',
                        'g.buyTotal <= :buyTotal'
                    )
                )
                ->setParameter('buyTimes', $params['buyTimes'])
                ->setParameter('buyTotal', $params['buyTotal']);
        }
    }

    public function getQueryKey(): string
    {
        return QueryKey::GROUP_SEARCH;
    }
}
