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

namespace Plugin\CustomerGroupRank42\Tests\Service\PurchaseFlow\Processor;

use Doctrine\Common\Collections\ArrayCollection;
use Eccube\Entity\Customer;
use Eccube\Entity\Order;
use Eccube\Entity\OrderItem;
use Eccube\Entity\Shipping;
use Eccube\Service\PurchaseFlow\Processor\DeliveryFeePreprocessor;
use Eccube\Service\PurchaseFlow\PurchaseContext;
use PHPUnit\Framework\TestCase;
use Plugin\CustomerGroup42\Entity\Group;
use Plugin\CustomerGroupRank42\Service\PurchaseFlow\Processor\GroupDeliveryFreePreprocessor;

class GroupDeliveryFreePreprocessorTest extends TestCase
{
    private $preprocessor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->preprocessor = new GroupDeliveryFreePreprocessor();
    }

    public function test送料無料条件を満たす場合は送料が0になる(): void
    {
        $group = $this->createMockGroup(1000, null);
        $customer = $this->createMockCustomer([$group]);
        $Order = $this->createMockOrder($customer, 1500);

        $context = $this->createMock(PurchaseContext::class);
        $this->preprocessor->process($Order, $context);

        foreach ($Order->getShippings() as $Shipping) {
            foreach ($Shipping->getOrderItems() as $Item) {
                if ($Item->getProcessorName() == DeliveryFeePreprocessor::class) {
                    self::assertEquals(0, $Item->getQuantity());
                }
            }
        }
    }

    public function test送料無料条件を満たさない場合は送料がそのまま(): void
    {
        $group = $this->createMockGroup(10000, null);
        $customer = $this->createMockCustomer([$group]);
        $Order = $this->createMockOrder($customer, 1500);

        $context = $this->createMock(PurchaseContext::class);
        $this->preprocessor->process($Order, $context);

        foreach ($Order->getShippings() as $Shipping) {
            foreach ($Shipping->getOrderItems() as $Item) {
                if ($Item->getProcessorName() == DeliveryFeePreprocessor::class) {
                    self::assertEquals(1, $Item->getQuantity());
                }
            }
        }
    }

    public function test数量条件を満たす場合は送料が0になる(): void
    {
        $group = $this->createMockGroup(null, 3);
        $customer = $this->createMockCustomer([$group]);
        $Order = $this->createMockOrder($customer, 1000, 5);

        $context = $this->createMock(PurchaseContext::class);
        $this->preprocessor->process($Order, $context);

        foreach ($Order->getShippings() as $Shipping) {
            foreach ($Shipping->getOrderItems() as $Item) {
                if ($Item->getProcessorName() == DeliveryFeePreprocessor::class) {
                    self::assertEquals(0, $Item->getQuantity());
                }
            }
        }
    }

    public function testグループに送料無料条件が設定されていない場合は何もしない(): void
    {
        $group = $this->createMockGroup(null, null);
        $customer = $this->createMockCustomer([$group]);
        $Order = $this->createMockOrder($customer, 1500);

        $context = $this->createMock(PurchaseContext::class);
        $this->preprocessor->process($Order, $context);

        foreach ($Order->getShippings() as $Shipping) {
            foreach ($Shipping->getOrderItems() as $Item) {
                if ($Item->getProcessorName() == DeliveryFeePreprocessor::class) {
                    self::assertEquals(1, $Item->getQuantity());
                }
            }
        }
    }

    public function testグループに所属していない場合は何もしない(): void
    {
        $customer = $this->createMockCustomer([]);
        $Order = $this->createMockOrder($customer, 1500);

        $context = $this->createMock(PurchaseContext::class);
        $this->preprocessor->process($Order, $context);

        foreach ($Order->getShippings() as $Shipping) {
            foreach ($Shipping->getOrderItems() as $Item) {
                if ($Item->getProcessorName() == DeliveryFeePreprocessor::class) {
                    self::assertEquals(1, $Item->getQuantity());
                }
            }
        }
    }

    private function createMockGroup(?float $deliveryFreeAmount, ?float $deliveryFreeQuantity): Group
    {
        $group = $this->createMock(Group::class);
        $group->method('getDeliveryFreeAmount')->willReturn($deliveryFreeAmount);
        $group->method('getDeliveryFreeQuantity')->willReturn($deliveryFreeQuantity);

        return $group;
    }

    private function createMockCustomer(array $groups): Customer
    {
        $customer = $this->createMock(Customer::class);
        $customer->method('hasGroups')->willReturn(count($groups) > 0);
        $customer->method('getGroups')->willReturn(new ArrayCollection($groups));

        return $customer;
    }

    private function createMockOrder($Customer, int $priceIncTax, int $quantity = 1): Order
    {
        // 商品明細（モック）
        $ProductItem = $this->createMock(OrderItem::class);
        $ProductItem->method('getPriceIncTax')->willReturn($priceIncTax);
        $ProductItem->method('getQuantity')->willReturn($quantity);
        $ProductItem->method('getProcessorName')->willReturn(null);

        // 送料明細（実オブジェクト - setQuantityを呼ぶため）
        $DeliveryFeeItem = new OrderItem();
        $DeliveryFeeItem->setQuantity(1);
        $DeliveryFeeItem->setProcessorName(DeliveryFeePreprocessor::class);

        // Shipping（モック）
        $Shipping = $this->createMock(Shipping::class);
        $Shipping->method('getProductOrderItems')->willReturn(new ArrayCollection([$ProductItem]));
        $Shipping->method('getOrderItems')->willReturn(new ArrayCollection([$ProductItem, $DeliveryFeeItem]));

        // Order（モック）
        $Order = $this->createMock(Order::class);
        $Order->method('getCustomer')->willReturn($Customer);
        $Order->method('getShippings')->willReturn(new ArrayCollection([$Shipping]));

        return $Order;
    }
}
