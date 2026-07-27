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

namespace Plugin\CustomerGroupRank44\Tests\Repository\QueryCustomizer;

use Eccube\Tests\EccubeTestCase;
use Plugin\CustomerGroup44\Entity\Group;
use Plugin\CustomerGroup44\Tests\TestCaseTrait;
use Plugin\CustomerGroupRank44\Repository\QueryCustomizer\GroupSearchCustomizer;

class GroupSearchCustomizerTest extends EccubeTestCase
{
    use TestCaseTrait;

    public function test既存のWHERE条件が上書きされないこと(): void
    {
        $customizer = new GroupSearchCustomizer();

        $qb = $this->entityManager->createQueryBuilder()
            ->select('g')
            ->from(Group::class, 'g')
            ->where('g.id = :id')
            ->setParameter('id', 1);

        $customizer->customize($qb, ['buyTimes' => 10, 'buyTotal' => 1000], '');

        $dql = $qb->getDQL();

        // 既存の条件が保持されていること
        self::assertStringContainsString('g.id = :id', $dql);
        // 新しい条件も追加されていること
        self::assertStringContainsString('g.buyTimes <= :buyTimes', $dql);
        self::assertStringContainsString('g.buyTotal <= :buyTotal', $dql);
    }

    public function test想定外のパラメータが含まれていても指定条件のみ追加されること(): void
    {
        $customizer = new GroupSearchCustomizer();

        $qb = $this->entityManager->createQueryBuilder()
            ->select('g')
            ->from(Group::class, 'g')
            ->where('g.id = :id')
            ->setParameter('id', 1);

        $customizer->customize($qb, [
            'buyTimes' => 10,
            'buyTotal' => 1000,
            'name' => 'test',
            'unknown' => 'value',
        ], '');

        $dql = $qb->getDQL();

        // 既存の条件が保持されていること
        self::assertStringContainsString('g.id = :id', $dql);
        // 指定条件のみ追加されていること
        self::assertStringContainsString('g.buyTimes <= :buyTimes', $dql);
        self::assertStringContainsString('g.buyTotal <= :buyTotal', $dql);
        // 想定外のパラメータが条件に含まれないこと
        self::assertStringNotContainsString('name', $dql);
        self::assertStringNotContainsString('unknown', $dql);
    }

    public function testパラメータが未指定の場合は条件が追加されないこと(): void
    {
        $customizer = new GroupSearchCustomizer();

        $qb = $this->entityManager->createQueryBuilder()
            ->select('g')
            ->from(Group::class, 'g')
            ->where('g.id = :id')
            ->setParameter('id', 1);

        $customizer->customize($qb, [], '');

        $dql = $qb->getDQL();

        self::assertStringContainsString('g.id = :id', $dql);
        self::assertStringNotContainsString('buyTimes', $dql);
        self::assertStringNotContainsString('buyTotal', $dql);
    }
}
