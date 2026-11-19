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

namespace Plugin\CustomerGroupRank42\Repository\QueryCustomizer;

use Doctrine\ORM\QueryBuilder;
use Eccube\Doctrine\Query\QueryCustomizer;
use Eccube\Util\StringUtil;
use Plugin\CustomerGroup42\Repository\QueryKey;
use Plugin\CustomerGroupRank42\Entity\Config;
use Plugin\CustomerGroupRank42\Repository\ConfigRepository;

/**
 * 会員の購入実績で、候補になる会員グループを絞る。
 *
 * 購入回数と購入金額の結合は設定（Entity\Config::$rankCondition）で選ぶ。
 *
 * - **OR（以前の版の挙動。既存の店の既定）**: どちらか一方を満たせば候補。
 *   条件の書き方は以前の版と一字一句同じにしてある（結果を変えないため）。
 * - **AND（新規インストールの既定）**: 両方を満たしたときだけ候補。
 *   空欄は「この条件は使わない」。`NULL <= :x` は真にならないので IS NULL を
 *   足さないと、片方だけ入れたグループが永久に一致しない。両方とも空の
 *   グループは手で割り当てるものなので候補に混ぜない（Rank::isRankGroup() と
 *   同じ線引き）。
 */
class GroupSearchCustomizer implements QueryCustomizer
{
    /**
     * @var ConfigRepository|null
     */
    private $configRepository;

    /**
     * 設定を読む。コンストラクタは以前の版のまま（引数なし）にしてあるので、
     * 店が `new GroupSearchCustomizer()` していても動く（その場合は OR）。
     *
     * @required
     */
    public function setConfigRepository(ConfigRepository $configRepository): void
    {
        $this->configRepository = $configRepository;
    }

    public function customize(QueryBuilder $builder, $params, $queryKey): void
    {
        if (
            isset($params['buyTimes'], $params['buyTotal'])
            && StringUtil::isNotBlank($params['buyTimes'])
            && StringUtil::isNotBlank($params['buyTotal'])
        ) {
            if (Config::CONDITION_AND === $this->getRankCondition()) {
                $condition = $builder->expr()->andX(
                    $builder->expr()->orX('g.buyTimes IS NULL', 'g.buyTimes <= :buyTimes'),
                    $builder->expr()->orX('g.buyTotal IS NULL', 'g.buyTotal <= :buyTotal'),
                    $builder->expr()->orX('g.buyTimes IS NOT NULL', 'g.buyTotal IS NOT NULL')
                );
            } else {
                $condition = $builder->expr()->orX(
                    'g.buyTimes <= :buyTimes',
                    'g.buyTotal <= :buyTotal'
                );
            }

            $builder
                ->andWhere($condition)
                ->setParameter('buyTimes', $params['buyTimes'])
                ->setParameter('buyTotal', $params['buyTotal']);
        }
    }

    public function getQueryKey(): string
    {
        return QueryKey::GROUP_SEARCH;
    }

    /**
     * いま効いている条件の結合。設定を読めないときは OR（以前の版の挙動）。
     */
    protected function getRankCondition(): string
    {
        if (null === $this->configRepository) {
            return Config::CONDITION_OR;
        }

        return $this->configRepository->getRankCondition();
    }
}
