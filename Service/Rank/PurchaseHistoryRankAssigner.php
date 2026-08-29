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

namespace Plugin\CustomerGroupRank44\Service\Rank;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Eccube\Entity\Customer;
use Plugin\CustomerGroup44\Entity\Group;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;

/**
 * 購入実績から会員グループを当てる、既定の当て方。
 *
 * **priority 100。** 店が独自の当て方を足したときに、既定より先に走らせたければ
 * これより大きくする。
 */
#[AsTaggedItem(priority: 100)]
class PurchaseHistoryRankAssigner implements RankAssignerInterface
{
    protected EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * 優先度が最上位のグループを会員に設定する
     */
    public function assign(Customer $customer): void
    {
        // ランク管理対象の会員グループだけを外す。
        //
        // 以前は所属グループを全て消していたが、購入実績の条件を持たない
        // グループは検索条件（buyTimes/buyTotal の比較）に決して一致しないため、
        // 会員登録アドオンや管理画面で割り当てたグループがログインのたびに
        // 失われ、二度と戻らなかった。会員グループは価格・閲覧可否・配送・
        // 支払方法を左右するので、ランクが管理していないものは触らない。
        /** @var Group $group */
        foreach ($customer->getGroups()->toArray() as $group) {
            if ($this->isRankGroup($group)) {
                $customer->removeGroup($group);
                $group->removeCustomer($customer);
            }
        }

        // 対象の会員グループが見つかったら登録
        $groups = $this->getGroups($customer);
        if ($groups->count() > 0) {
            /** @var Group $group */
            $group = $groups->first();
            $customer->addGroup($group);
            $group->addCustomer($customer);
        }
    }

    /**
     * ランクが管理するグループか。
     *
     * 購入回数・購入金額のいずれかが設定されていればランク用とみなす。
     * どちらも未設定のグループは購入実績では到達できないため、ランクの
     * 管理外（手動で割り当てるもの）として扱う。
     */
    protected function isRankGroup(Group $group): bool
    {
        return null !== $group->getBuyTimes() || null !== $group->getBuyTotal();
    }

    /**
     * 会員に適用可能なグループ一覧を取得
     */
    protected function getGroups(Customer $customer): ArrayCollection
    {
        $searchData = [
            'buyTimes' => $customer->getBuyTimes(),
            'buyTotal' => $customer->getBuyTotal(),
        ];
        $groups = $this->entityManager->getRepository(Group::class)->getQueryBuilderBySearchData($searchData)
            ->getQuery()
            ->getResult();

        return new ArrayCollection($groups);
    }
}
