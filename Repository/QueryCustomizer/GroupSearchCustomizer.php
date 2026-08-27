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

/**
 * 会員の購入実績で、候補になる会員グループを絞る。
 *
 * **購入回数と購入金額は AND。** 「3回かつ30万円」と読ませたい条件なので、
 * 金額だけ足りている会員に上位ランクは付けない。ランクに卸価格を紐づけている
 * 店では、片方だけで上がれると実害が出る。
 *
 * **空欄は「この条件は使わない」。** 片方だけで判定したい店は、グループ側の
 * もう一方を空にする。`NULL <= :x` は真にならないので、IS NULL を足さないと
 * 片方だけ入れたグループが永久に一致しない。
 *
 * **両方とも空のグループは候補に混ぜない。** ランクではなく、手で割り当てる
 * ものだからそのまま残す（`PurchaseHistoryRankAssigner::isRankGroup()` と
 * 同じ線引き）。
 */
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
                    $builder->expr()->andX(
                        $builder->expr()->orX('g.buyTimes IS NULL', 'g.buyTimes <= :buyTimes'),
                        $builder->expr()->orX('g.buyTotal IS NULL', 'g.buyTotal <= :buyTotal'),
                        $builder->expr()->orX('g.buyTimes IS NOT NULL', 'g.buyTotal IS NOT NULL')
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
