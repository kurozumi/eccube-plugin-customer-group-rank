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

namespace Plugin\CustomerGroupRank42\Service\Rank;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Eccube\Entity\Customer;
use Plugin\CustomerGroup42\Entity\Group;

class Rank implements RankInterface
{
    protected EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * 優先度が最上位のグループを会員に設定する
     */
    public function apply(Customer $customer): void
    {
        // 会員グループをクリアする
        $customer->getGroups()->clear();

        // 対象の会員グループが見つかったら登録
        $groups = $this->getGroups($customer);
        if ($groups->count() > 0) {
            /** @var Group $group */
            $group = $groups->first();
            $customer->addGroup($group);
        }
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
