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
use Plugin\CustomerGroupRank44\Service\Rank\Context;
use Plugin\CustomerGroupRank44\Service\Rank\RankInterface;

class ContextTest extends TestCase
{
    public function test登録された全てのRankが実行される(): void
    {
        $rank1 = $this->createMock(RankInterface::class);
        $rank1->expects(self::once())->method('apply');

        $rank2 = $this->createMock(RankInterface::class);
        $rank2->expects(self::once())->method('apply');

        $context = new Context([$rank1, $rank2]);

        $customer = $this->createMock(Customer::class);
        $context->apply($customer);
    }

    /**
     * 1つも無ければ何もしない。
     *
     * **タグ付けが外れるとこの状態になる。** 例外は出ず、ランクが当たらない
     * まま通ってしまうので、コンテナ越しの検証（`RankRegistrationTest`）も要る。
     */
    public function testRank未登録の場合はエラーにならない(): void
    {
        $context = new Context();
        $customer = $this->createMock(Customer::class);

        $context->apply($customer);

        self::assertTrue(true);
    }
}
