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

namespace Plugin\CustomerGroupRank44\Service\PurchaseFlow\Processor;

use Eccube\Entity\ItemHolderInterface;
use Eccube\Entity\Order;
use Eccube\Service\PurchaseFlow\ItemHolderPreprocessor;
use Eccube\Service\PurchaseFlow\Processor\DeliveryFeePreprocessor;
use Eccube\Service\PurchaseFlow\PurchaseContext;
use Plugin\CustomerGroup44\Entity\Group;

/**
 * 会員グループごとの送料無料条件を適用する
 */
class GroupDeliveryFreePreprocessor implements ItemHolderPreprocessor
{
    public function process(ItemHolderInterface $itemHolder, PurchaseContext $context): void
    {
        if (!$itemHolder instanceof Order) {
            return;
        }

        $Customer = $itemHolder->getCustomer();
        if (!$Customer || !$Customer->hasGroups()) {
            return;
        }

        // 条件が設定された最初のグループで判定する（会員グループ価格アドオンと
        // 同じ「並び順で最初に見つかったもの」の考え方）。所属グループの反復順は
        // 並び順を保証しないため、ここで優先度順（sortNo 昇順）に並べ替える。
        $groups = $Customer->getGroups()->toArray();
        usort($groups, function (Group $a, Group $b) {
            return $a->getSortNo() <=> $b->getSortNo();
        });

        /** @var Group $group */
        foreach ($groups as $group) {
            $deliveryFreeAmount = $group->getDeliveryFreeAmount();
            $deliveryFreeQuantity = $group->getDeliveryFreeQuantity();

            if (!$deliveryFreeAmount && !$deliveryFreeQuantity) {
                continue;
            }

            foreach ($itemHolder->getShippings() as $Shipping) {
                $isFree = false;
                $total = 0;
                $quantity = 0;

                foreach ($Shipping->getProductOrderItems() as $Item) {
                    $total += $Item->getPriceIncTax() * $Item->getQuantity();
                    $quantity += $Item->getQuantity();
                }

                if ($deliveryFreeAmount && $total >= $deliveryFreeAmount) {
                    $isFree = true;
                }

                if ($deliveryFreeQuantity && $quantity >= $deliveryFreeQuantity) {
                    $isFree = true;
                }

                if ($isFree) {
                    foreach ($Shipping->getOrderItems() as $Item) {
                        if ($Item->getProcessorName() == DeliveryFeePreprocessor::class) {
                            $Item->setQuantity(0);
                        }
                    }
                }
            }

            // 最初のグループの条件で判定
            break;
        }
    }
}
