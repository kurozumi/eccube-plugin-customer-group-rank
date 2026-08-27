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

namespace Plugin\CustomerGroupRank44\Tests\Repository;

use Eccube\Tests\EccubeTestCase;
use Plugin\CustomerGroup44\Repository\GroupRepository;
use Plugin\CustomerGroup44\Tests\TestCaseTrait;
use PHPUnit\Framework\Attributes\DataProvider;

class GroupRepositoryTest extends EccubeTestCase
{
    use TestCaseTrait;

    protected $groupRepository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->groupRepository = static::getContainer()->get(GroupRepository::class);
    }

    #[DataProvider('conditionProvider')]
    public function testランクアップ条件にマッチした会員グループが見つかるか($groupTimes, $groupTotal, $customerTimes, $customerTotal, $expected): void
    {
        $group = $this->createGroup();
        $group->setBuyTimes($groupTimes);
        $group->setBuyTotal($groupTotal);

        $customer = $this->createCustomer();
        $customer->setBuyTimes($customerTimes);
        $customer->setBuyTotal($customerTotal);

        $this->entityManager->flush();

        $results = $this->groupRepository->getQueryBuilderBySearchData([
            'buyTimes' => $customer->getBuyTimes(),
            'buyTotal' => $customer->getBuyTotal(),
        ])->getQuery()->getResult();

        self::assertCount($expected, $results);
    }

    /**
     * **購入回数と購入金額は AND。** 片方だけ達成しても候補にならない。
     *
     * 以前は OR で、「購入回数のみ達成」「購入金額のみ達成」も1件と数えていた。
     * 金額だけで上位ランクを取れるので、ランクに卸価格を紐づけている店で
     * 実害が出る。
     *
     * 最後の2つは**空欄の扱い**。空欄は「この条件は使わない」なので、
     * 残ったほうだけで判定する。
     */
    public static function conditionProvider(): array
    {
        return [
            '購入回数・金額ともに未達' => [1, 1, 0, 0, 0],
            '購入回数のみ達成' => [10, 1000, 10, 10, 0],
            '購入金額のみ達成' => [10, 1000, 9, 1000, 0],
            '購入回数・金額ともに達成' => [10, 1000, 10, 1000, 1],
            '購入金額が空のグループは回数だけで判定する' => [10, null, 10, 0, 1],
            '購入回数が空のグループは金額だけで判定する' => [null, 1000, 0, 1000, 1],
        ];
    }
}
