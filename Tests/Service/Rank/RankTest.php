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
use Eccube\Tests\EccubeTestCase;
use Plugin\CustomerGroup44\Tests\TestCaseTrait;
use Plugin\CustomerGroupRank44\Service\Rank\Context;

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

    /**
     * ランク条件を持たないグループは、ランク判定で外さない。
     *
     * 会員登録アドオンや管理画面で割り当てたグループは購入実績の条件を
     * 持たないため、検索条件に一致しない。以前は所属グループを全て消して
     * いたので、ログインのたびにそれらが失われ、二度と戻らなかった。
     */
    public function test手動で割り当てたグループはランク判定で外れない(): void
    {
        $manual = $this->createGroup('手動割当グループ');
        $manual->setSortNo(1);

        $rank = $this->createGroup('ゴールド');
        $rank->setBuyTimes(1);
        $rank->setBuyTotal(1000);
        $rank->setSortNo(2);

        $customer = $this->createCustomer();
        $customer->setBuyTimes(5);
        $customer->setBuyTotal(50000);
        $customer->addGroup($manual);
        $manual->addCustomer($customer);

        $this->entityManager->flush();

        $this->context->apply($customer);
        $this->entityManager->flush();

        $names = $this->entityManager->find(Customer::class, $customer->getId())
            ->getGroups()
            ->map(function ($group) {
                return $group->getName();
            })
            ->toArray();

        self::assertContains($manual->getName(), $names, '手動で割り当てたグループが失われています');
        self::assertContains($rank->getName(), $names, 'ランクのグループが設定されていません');
    }

    /**
     * 条件に一致するランクが無くても、手動で割り当てたグループは残る。
     */
    public function test購入実績が無くても手動で割り当てたグループは残る(): void
    {
        $manual = $this->createGroup('手動割当グループ');

        $rank = $this->createGroup('ゴールド');
        $rank->setBuyTimes(10);
        $rank->setBuyTotal(100000);

        $customer = $this->createCustomer();
        $customer->addGroup($manual);
        $manual->addCustomer($customer);

        $this->entityManager->flush();

        $this->context->apply($customer);
        $this->entityManager->flush();

        $names = $this->entityManager->find(Customer::class, $customer->getId())
            ->getGroups()
            ->map(function ($group) {
                return $group->getName();
            })
            ->toArray();

        self::assertSame([$manual->getName()], $names, '手動で割り当てたグループが失われています');
    }
}
