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

namespace Plugin\CustomerGroupRank42\Tests\Service\Rank;

use Eccube\Entity\Customer;
use Eccube\Tests\EccubeTestCase;
use Plugin\CustomerGroup42\Tests\TestCaseTrait;
use Plugin\CustomerGroupRank42\Service\Rank\Context;

class RankTest extends EccubeTestCase
{
    use TestCaseTrait;

    protected $context;

    protected function setUp(): void
    {
        parent::setUp();

        $this->context = static::getContainer()->get(Context::class);
    }

    public function test優先度が最上位のグループが設定される(): void
    {
        $group1 = $this->createGroup();
        $group1->setBuyTimes(1);
        $group1->setBuyTotal(1000);
        $group1->setSortNo(2);
        $group2 = $this->createGroup();
        $group2->setBuyTimes(2);
        $group2->setBuyTotal(2000);
        $group2->setSortNo(1);

        $customer = $this->createCustomer();
        $customer->setBuyTimes(2);
        $customer->setBuyTotal(2000);

        $this->entityManager->flush();

        $this->context->apply($customer);

        $groups = $this->entityManager->find(Customer::class, $customer->getId())->getGroups();

        self::assertEquals($group2, $groups->first());
    }

    public function test条件にマッチするグループがない場合はグループが空になる(): void
    {
        $group = $this->createGroup();
        $group->setBuyTimes(10);
        $group->setBuyTotal(10000);
        $group->setSortNo(1);

        $customer = $this->createCustomer();
        $customer->setBuyTimes(0);
        $customer->setBuyTotal(0);

        $this->entityManager->flush();

        $this->context->apply($customer);

        $groups = $this->entityManager->find(Customer::class, $customer->getId())->getGroups();

        self::assertCount(0, $groups);
    }

    public function test既存のグループがクリアされてから新しいグループが設定される(): void
    {
        $group1 = $this->createGroup();
        $group1->setBuyTimes(1);
        $group1->setBuyTotal(1000);
        $group1->setSortNo(1);

        $group2 = $this->createGroup();
        $group2->setBuyTimes(5);
        $group2->setBuyTotal(5000);
        $group2->setSortNo(2);

        $customer = $this->createCustomer();
        $customer->setBuyTimes(1);
        $customer->setBuyTotal(1000);
        $customer->addGroup($group2);

        $this->entityManager->flush();

        $this->context->apply($customer);

        $groups = $this->entityManager->find(Customer::class, $customer->getId())->getGroups();

        self::assertCount(1, $groups);
        self::assertEquals($group1, $groups->first());
    }
}
