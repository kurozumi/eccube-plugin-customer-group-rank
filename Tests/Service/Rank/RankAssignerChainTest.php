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

use Eccube\Entity\Customer;
use PHPUnit\Framework\TestCase;
use Plugin\CustomerGroupRank44\Service\Rank\RankAssignerChain;
use Plugin\CustomerGroupRank44\Service\Rank\RankAssignerInterface;

class RankAssignerChainTest extends TestCase
{
    public function test登録された全てのRankが実行される(): void
    {
        $rank1 = $this->createMock(RankAssignerInterface::class);
        $rank1->expects(self::once())->method('assign');

        $rank2 = $this->createMock(RankAssignerInterface::class);
        $rank2->expects(self::once())->method('assign');

        $chain = new RankAssignerChain([$rank1, $rank2]);

        $customer = $this->createMock(Customer::class);
        $chain->assign($customer);
    }

    /**
     * 1つも無ければ何もしない。
     *
     * **タグ付けが外れるとこの状態になる。** 例外は出ず、ランクが当たらない
     * まま通ってしまうので、コンテナ越しの検証（`RankRegistrationTest`）も要る。
     */
    public function testRank未登録の場合はエラーにならない(): void
    {
        $chain = new RankAssignerChain();
        $customer = $this->createMock(Customer::class);

        $chain->assign($customer);

        self::assertTrue(true);
    }
}
