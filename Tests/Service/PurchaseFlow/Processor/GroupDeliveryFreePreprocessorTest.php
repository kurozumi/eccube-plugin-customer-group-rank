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

use Eccube\Entity\Master\OrderItemType;
use Eccube\Entity\Order;
use Eccube\Entity\OrderItem;
use Eccube\Entity\Shipping;
use Eccube\Service\PurchaseFlow\Processor\DeliveryFeePreprocessor;
use Eccube\Service\PurchaseFlow\PurchaseContext;
use Eccube\Tests\EccubeTestCase;
use Plugin\CustomerGroup42\Tests\TestCaseTrait;
use Plugin\CustomerGroupRank42\Service\PurchaseFlow\Processor\GroupDeliveryFreePreprocessor;

class GroupDeliveryFreePreprocessorTest extends EccubeTestCase
{
    use TestCaseTrait;

    private GroupDeliveryFreePreprocessor $preprocessor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->preprocessor = new GroupDeliveryFreePreprocessor();
    }

    public function test送料無料条件を満たす場合は送料が0になる(): void
    {
        $group = $this->createGroup();
        $group->setDeliveryFreeAmount(1000);

        $customer = $this->createCustomer();
        $customer->addGroup($group);

        $Order = $this->createOrderWithShipping($customer, 1500);

        $this->entityManager->flush();

        $context = new PurchaseContext($Order, $customer);
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
        $group = $this->createGroup();
        $group->setDeliveryFreeAmount(10000);

        $customer = $this->createCustomer();
        $customer->addGroup($group);

        $Order = $this->createOrderWithShipping($customer, 1500);

        $this->entityManager->flush();

        $context = new PurchaseContext($Order, $customer);
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
        $group = $this->createGroup();
        $group->setDeliveryFreeQuantity(3);

        $customer = $this->createCustomer();
        $customer->addGroup($group);

        $Order = $this->createOrderWithShipping($customer, 1000, 5);

        $this->entityManager->flush();

        $context = new PurchaseContext($Order, $customer);
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
        $group = $this->createGroup();

        $customer = $this->createCustomer();
        $customer->addGroup($group);

        $Order = $this->createOrderWithShipping($customer, 1500);

        $this->entityManager->flush();

        $context = new PurchaseContext($Order, $customer);
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
        $customer = $this->createCustomer();

        $Order = $this->createOrderWithShipping($customer, 1500);

        $this->entityManager->flush();

        $context = new PurchaseContext($Order, $customer);
        $this->preprocessor->process($Order, $context);

        foreach ($Order->getShippings() as $Shipping) {
            foreach ($Shipping->getOrderItems() as $Item) {
                if ($Item->getProcessorName() == DeliveryFeePreprocessor::class) {
                    self::assertEquals(1, $Item->getQuantity());
                }
            }
        }
    }

    private function createOrderWithShipping($Customer, int $priceIncTax, int $quantity = 1): Order
    {
        $Order = new Order();
        $Order->setCustomer($Customer);

        $Shipping = new Shipping();
        $Shipping->setOrder($Order);
        $Order->addShipping($Shipping);

        // 商品明細
        $ProductItem = new OrderItem();
        $ProductItem->setShipping($Shipping);
        $ProductItem->setOrder($Order);
        $ProductItem->setPriceIncTax($priceIncTax);
        $ProductItem->setQuantity($quantity);
        $ProductItem->setOrderItemType($this->entityManager->find(OrderItemType::class, OrderItemType::PRODUCT));
        $Shipping->addOrderItem($ProductItem);
        $Order->addOrderItem($ProductItem);

        // 送料明細
        $DeliveryFeeItem = new OrderItem();
        $DeliveryFeeItem->setShipping($Shipping);
        $DeliveryFeeItem->setOrder($Order);
        $DeliveryFeeItem->setQuantity(1);
        $DeliveryFeeItem->setProcessorName(DeliveryFeePreprocessor::class);
        $DeliveryFeeItem->setOrderItemType($this->entityManager->find(OrderItemType::class, OrderItemType::DELIVERY_FEE));
        $Shipping->addOrderItem($DeliveryFeeItem);
        $Order->addOrderItem($DeliveryFeeItem);

        $this->entityManager->persist($Order);

        return $Order;
    }
}
