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

namespace Plugin\CustomerGroupRank42\Tests\Repository;

use Eccube\Tests\EccubeTestCase;
use Plugin\CustomerGroup42\Entity\Group;
use Plugin\CustomerGroup42\Repository\GroupRepository;
use Plugin\CustomerGroup42\Tests\TestCaseTrait;
use Plugin\CustomerGroupRank42\Entity\Config;
use Plugin\CustomerGroupRank42\Tests\RankConditionTrait;

class GroupRepositoryTest extends EccubeTestCase
{
    use RankConditionTrait;
    use TestCaseTrait;

    protected $groupRepository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->groupRepository = static::getContainer()->get(GroupRepository::class);
    }

    /**
     * 作ったグループが候補に入るか。
     *
     * 件数ではなく作ったグループで見る。店の DB には条件の違うランクが
     * 他にもあるので、件数だと環境で結果が変わる。
     */
    private function isCandidate(?float $groupTimes, ?float $groupTotal, float $customerTimes, float $customerTotal): bool
    {
        $group = $this->createGroup('ランク判定テスト');
        $group->setBuyTimes($groupTimes);
        $group->setBuyTotal($groupTotal);
        $this->entityManager->flush();

        $results = $this->groupRepository->getQueryBuilderBySearchData([
            'buyTimes' => $customerTimes,
            'buyTotal' => $customerTotal,
        ])->getQuery()->getResult();

        return in_array($group->getId(), array_map(function (Group $g) {
            return $g->getId();
        }, $results), true);
    }

    /**
     * 以前の版の挙動（OR）。既存の店はこれのまま。
     *
     * @dataProvider orProvider
     */
    public function testORではどちらか一方を満たせば候補になる(?float $groupTimes, ?float $groupTotal, float $customerTimes, float $customerTotal, bool $expected): void
    {
        $this->useRankCondition(Config::CONDITION_OR);

        self::assertSame($expected, $this->isCandidate($groupTimes, $groupTotal, $customerTimes, $customerTotal));
    }

    /**
     * 設定の行が無い店（以前の版から git pull で上げた店）は OR のまま。
     *
     * @dataProvider orProvider
     */
    public function test設定の行が無ければORで判定する(?float $groupTimes, ?float $groupTotal, float $customerTimes, float $customerTotal, bool $expected): void
    {
        $this->useRankCondition(null);

        self::assertSame($expected, $this->isCandidate($groupTimes, $groupTotal, $customerTimes, $customerTotal));
    }

    public function orProvider(): array
    {
        return [
            '購入回数・金額ともに未達' => [1, 1, 0, 0, false],
            '購入回数のみ達成' => [10, 1000, 10, 10, true],
            '購入金額のみ達成' => [10, 1000, 9, 1000, true],
            '購入回数・金額ともに達成' => [10, 1000, 10, 1000, true],
            '購入回数だけの条件を満たす' => [10, null, 10, 0, true],
            '購入金額だけの条件を満たす' => [null, 1000, 0, 1000, true],
            '両方とも空のグループは候補にならない' => [null, null, 100, 100000, false],
        ];
    }

    /**
     * @dataProvider andProvider
     */
    public function testANDでは両方を満たしたときだけ候補になる(?float $groupTimes, ?float $groupTotal, float $customerTimes, float $customerTotal, bool $expected): void
    {
        $this->useRankCondition(Config::CONDITION_AND);

        self::assertSame($expected, $this->isCandidate($groupTimes, $groupTotal, $customerTimes, $customerTotal));
    }

    public function andProvider(): array
    {
        return [
            '購入回数・金額ともに未達' => [1, 1, 0, 0, false],
            '購入回数のみ達成' => [10, 1000, 10, 10, false],
            '購入金額のみ達成' => [10, 1000, 9, 1000, false],
            '購入回数・金額ともに達成' => [10, 1000, 10, 1000, true],
            '購入回数だけの条件（金額は空欄）を満たす' => [10, null, 10, 0, true],
            '購入回数だけの条件（金額は空欄）に届かない' => [10, null, 9, 100000, false],
            '購入金額だけの条件（回数は空欄）を満たす' => [null, 1000, 0, 1000, true],
            '購入金額だけの条件（回数は空欄）に届かない' => [null, 1000, 100, 999, false],
            '両方とも空のグループは候補にならない' => [null, null, 100, 100000, false],
        ];
    }
}
