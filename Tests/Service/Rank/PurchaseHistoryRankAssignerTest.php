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
use Plugin\CustomerGroupRank44\Service\Rank\RankAssignerChain;

class PurchaseHistoryRankAssignerTest extends EccubeTestCase
{
    use TestCaseTrait;

    /** @var RankAssignerChain */
    protected $chain;

    protected function setUp(): void
    {
        parent::setUp();

        $this->chain = static::getContainer()->get(RankAssignerChain::class);
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

        $this->chain->assign($customer);

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

        $this->chain->assign($customer);

        $groups = $this->entityManager->find(Customer::class, $customer->getId())->getGroups();

        self::assertCount(0, $groups);
    }

    /**
     * **購入回数と購入金額は AND。** 片方だけでは付かない。
     *
     * 以前は OR だったため、「3回かつ30万円」のゴールドに 2回・33万円の会員が
     * 入っていた。金額だけで上位ランクを取れるので、卸価格を紐づけている店では
     * 実害が出る。既存の検査は DQL に条件文字列が含まれるかしか見ておらず、
     * AND と OR の違いを捉えていなかった。
     */
    public function test購入回数と購入金額の片方だけでは付かない(): void
    {
        $group = $this->createGroup('AND検査ゴールド');
        $group->setBuyTimes(3);
        $group->setBuyTotal(300000);
        $group->setSortNo(1);

        // 金額は足りているが、回数が足りない
        $customer = $this->createCustomer();
        $customer->setBuyTimes(2);
        $customer->setBuyTotal(330000);

        $this->entityManager->flush();

        $this->chain->assign($customer);

        // **件数では見ない。** 店の DB には条件の違うランクが他にもあり、
        // 下位のランクが付くのは正しい。見たいのは「回数が足りないこの
        // グループが付いていないこと」だけ
        self::assertNotContains(
            $group->getName(),
            $this->assignedNames($customer),
            '購入回数が足りないのにランクが付いています'
        );
    }

    /**
     * 片方だけで判定したい店のために、**グループ側を空にしたらもう一方だけが効く**。
     */
    public function test条件を片方だけ入れたグループは残ったほうだけで判定される(): void
    {
        $group = $this->createGroup('金額を空にしたランク');
        $group->setBuyTimes(3);
        $group->setBuyTotal(null);
        $group->setSortNo(1);

        $customer = $this->createCustomer();
        $customer->setBuyTimes(5);
        $customer->setBuyTotal(0);

        $this->entityManager->flush();

        $this->chain->assign($customer);

        self::assertContains(
            $group->getName(),
            $this->assignedNames($customer),
            '購入金額を空にしたグループが付いていません'
        );
    }

    /**
     * @return string[]
     */
    private function assignedNames(Customer $customer): array
    {
        return $this->entityManager->find(Customer::class, $customer->getId())
            ->getGroups()
            ->map(function ($group) {
                return $group->getName();
            })
            ->toArray();
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

        $this->chain->assign($customer);

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

        $this->chain->assign($customer);
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

        $this->chain->assign($customer);
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
