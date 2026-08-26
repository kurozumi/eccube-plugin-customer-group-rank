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

namespace Plugin\CustomerGroupRank44\Tests\Service\Rank;

use Eccube\Tests\EccubeTestCase;
use Plugin\CustomerGroupRank44\Service\Rank\RankAssignerChain;
use Plugin\CustomerGroupRank44\Service\Rank\PurchaseHistoryRankAssigner;

/**
 * 既定の当て方がコンテナ越しに集まっていること。
 *
 * **集まらなくても例外は出ない。** ランクが当たらないまま通るだけなので、
 * `RankAssignerChainTest`（畳み方だけを見る）では捕まらない。タグ付け
 * （`RankAssignerInterface` の `#[AutoconfigureTag]`）を外すとここが落ちる。
 */
class RankRegistrationTest extends EccubeTestCase
{
    public function test既定のランクが集まっている(): void
    {
        /** @var RankAssignerChain $chain */
        $chain = static::getContainer()->get(RankAssignerChain::class);

        $ranks = (function () {
            return $this->ranks;
        })->call($chain);

        $classes = [];
        foreach ($ranks as $rank) {
            $classes[] = $rank::class;
        }

        self::assertContains(
            PurchaseHistoryRankAssigner::class,
            $classes,
            '既定のランクが集まっていない。RankAssignerInterface の #[AutoconfigureTag] を確かめること'
        );
    }
}
