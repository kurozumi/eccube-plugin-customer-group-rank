<?php

/*
 * This file is part of CustomerGroupRank42
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
use Plugin\CustomerGroup42\Repository\GroupRepository;
use Plugin\CustomerGroup42\Tests\TestCaseTrait;

class GroupRepositoryTest extends EccubeTestCase
{
    use TestCaseTrait;

    /**
     * @var array
     */
    protected $Results;

    /**
     * @var array
     */
    protected $searchData = [];

    /**
     * @var GroupRepository
     */
    protected $groupRepository;

    protected function setUp(): void
    {
        parent::setUp();

        $this->groupRepository = static::getContainer()->get(GroupRepository::class);
    }

    public function scenario()
    {
        $this->Results = $this->groupRepository->getQueryBuilderBySearchData($this->searchData)
            ->getQuery()
            ->getResult();
    }

    /**
     * @param $group_times
     * @param $group_total
     * @param $customer_times
     * @param $customer_total
     * @param $expected
     *
     * @dataProvider conditionProvider
     */
    public function testランクアップ条件にマッチした会員グループが見つかるか($group_times, $group_total, $customer_times, $customer_total, $expected)
    {
        $group = $this->createGroup();
        $group->setBuyTimes($group_times);
        $group->setBuyTotal($group_total);

        $customer = $this->createCustomer();
        $customer->setBuyTimes($customer_times);
        $customer->setBuyTotal($customer_total);

        $this->entityManager->flush();

        // 絞り込み条件
        $this->searchData = [
            'buyTimes' => $customer->getBuyTimes(),
            'buyTotal' => $customer->getBuyTotal(),
        ];

        $this->scenario();

        self::assertCount($expected, $this->Results);
    }

    public function conditionProvider()
    {
        return [
            [1, 1, 0, 0, 0],
            [10, 1000, 10, 10, 1],
            [10, 1000, 9, 1000, 1],
            [10, 1000, 10, 1000, 1],
        ];
    }
}
