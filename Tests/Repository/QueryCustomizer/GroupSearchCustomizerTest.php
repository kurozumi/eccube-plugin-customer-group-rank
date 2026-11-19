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

namespace Plugin\CustomerGroupRank42\Tests\Repository\QueryCustomizer;

use Eccube\Tests\EccubeTestCase;
use Plugin\CustomerGroup42\Entity\Group;
use Plugin\CustomerGroup42\Tests\TestCaseTrait;
use Plugin\CustomerGroupRank42\Entity\Config;
use Plugin\CustomerGroupRank42\Repository\ConfigRepository;
use Plugin\CustomerGroupRank42\Repository\QueryCustomizer\GroupSearchCustomizer;

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

    private function customizedDql(GroupSearchCustomizer $customizer): string
    {
        $qb = $this->entityManager->createQueryBuilder()
            ->select('g')
            ->from(Group::class, 'g');

        $customizer->customize($qb, ['buyTimes' => 10, 'buyTotal' => 1000], '');

        return $qb->getDQL();
    }

    /**
     * 店が `new GroupSearchCustomizer()` しているときも以前の版と同じ OR の条件になる。
     */
    public function test設定を渡さずに作ると以前の版と同じORの条件になる(): void
    {
        $dql = $this->customizedDql(new GroupSearchCustomizer());

        self::assertStringContainsString('g.buyTimes <= :buyTimes OR g.buyTotal <= :buyTotal', $dql);
        self::assertStringNotContainsString('IS NULL', $dql);
    }

    public function testANDの設定では両方の条件と空欄の扱いが入る(): void
    {
        $repository = $this->createMock(ConfigRepository::class);
        $repository->method('getRankCondition')->willReturn(Config::CONDITION_AND);

        $customizer = new GroupSearchCustomizer();
        $customizer->setConfigRepository($repository);

        $dql = $this->customizedDql($customizer);

        self::assertStringContainsString('g.buyTimes IS NULL OR g.buyTimes <= :buyTimes', $dql);
        self::assertStringContainsString('g.buyTotal IS NULL OR g.buyTotal <= :buyTotal', $dql);
        self::assertStringContainsString('g.buyTimes IS NOT NULL OR g.buyTotal IS NOT NULL', $dql);
    }

    public function testORの設定では以前の版と同じ条件になる(): void
    {
        $repository = $this->createMock(ConfigRepository::class);
        $repository->method('getRankCondition')->willReturn(Config::CONDITION_OR);

        $customizer = new GroupSearchCustomizer();
        $customizer->setConfigRepository($repository);

        $dql = $this->customizedDql($customizer);

        self::assertStringContainsString('g.buyTimes <= :buyTimes OR g.buyTotal <= :buyTotal', $dql);
        self::assertStringNotContainsString('IS NULL', $dql);
    }

    /**
     * コンテナから取ったもの（本番と同じ配線）に設定が届いていること。
     *
     * `@required` のセッタが呼ばれていないと、設定を AND にしても黙って OR で
     * 判定し続ける。
     */
    public function testコンテナのカスタマイザに設定が届いている(): void
    {
        /** @var GroupSearchCustomizer $customizer */
        $customizer = static::getContainer()->get(GroupSearchCustomizer::class);

        $property = new \ReflectionProperty(GroupSearchCustomizer::class, 'configRepository');
        $property->setAccessible(true);

        self::assertInstanceOf(ConfigRepository::class, $property->getValue($customizer));
    }
}
