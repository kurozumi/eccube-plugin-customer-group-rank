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

namespace Plugin\CustomerGroupRank44\Tests\Service\PurchaseFlow\Processor;

use Doctrine\Common\Collections\ArrayCollection;
use Eccube\Entity\Customer;
use Eccube\Entity\Order;
use Eccube\Entity\OrderItem;
use Eccube\Entity\Shipping;
use Eccube\Service\PurchaseFlow\Processor\DeliveryFeePreprocessor;
use Eccube\Service\PurchaseFlow\ItemCollection;
use Eccube\Service\PurchaseFlow\PurchaseContext;
use PHPUnit\Framework\TestCase;
use Plugin\CustomerGroup44\Entity\Group;
use Plugin\CustomerGroupRank44\Tests\EntityProxyLoader;
use Plugin\CustomerGroupRank44\Service\PurchaseFlow\Processor\GroupDeliveryFreePreprocessor;

class GroupDeliveryFreePreprocessorTest extends TestCase
{
    private $preprocessor;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        // Group はプラグインが拡張したエンティティで、そのプロキシは
        // カーネル起動時にしか読み込まれない。このテストはカーネルを
        // 起動しないため、単体で実行すると拡張前のクラスが読まれて
        // モックを組めなくなる。
        EntityProxyLoader::load();
    }

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

    private function createMockGroup(?float $deliveryFreeAmount, ?float $deliveryFreeQuantity, int $sortNo = 0): Group
    {
        $group = $this->createMock(Group::class);
        $group->method('getDeliveryFreeAmount')->willReturn($deliveryFreeAmount);
        $group->method('getDeliveryFreeQuantity')->willReturn($deliveryFreeQuantity);
        $group->method('getSortNo')->willReturn($sortNo);

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
        // EC-CUBE 4.4 では OrderItem::getPriceIncTax() / getQuantity() の
        // 戻り値型が string 宣言になっているため、モックも string を返す。
        $ProductItem = $this->createMock(OrderItem::class);
        $ProductItem->method('getPriceIncTax')->willReturn((string) $priceIncTax);
        $ProductItem->method('getQuantity')->willReturn((string) $quantity);
        $ProductItem->method('getProcessorName')->willReturn(null);

        // 送料明細（実オブジェクト - setQuantityを呼ぶため）
        $DeliveryFeeItem = new OrderItem();
        $DeliveryFeeItem->setQuantity(1);
        $DeliveryFeeItem->setProcessorName(DeliveryFeePreprocessor::class);

        // Shipping（モック）
        // EC-CUBE 4.4 では戻り値型が宣言されている。
        //   getProductOrderItems(): array
        //   getOrderItems(): ItemCollection
        $Shipping = $this->createMock(Shipping::class);
        $Shipping->method('getProductOrderItems')->willReturn([$ProductItem]);
        $Shipping->method('getOrderItems')->willReturn(new ItemCollection([$ProductItem, $DeliveryFeeItem]));

        // Order（モック）
        $Order = $this->createMock(Order::class);
        $Order->method('getCustomer')->willReturn($Customer);
        $Order->method('getShippings')->willReturn(new ArrayCollection([$Shipping]));

        return $Order;
    }

    /**
     * 複数のグループに条件があるときは、優先度が上のグループで判定する。
     *
     * 所属グループの反復順は並び順を保証しないため、意図的に逆順で渡す。
     * 並べ替えが無いと、優先度が下のグループ（厳しい条件）で判定されてしまう。
     */
    public function test複数グループでは優先度が上のグループの条件で判定する(): void
    {
        // 優先度が下（sortNo が大きい）＝厳しい条件
        $low = $this->createMockGroup(10000, null, 2);
        // 優先度が上（sortNo が小さい）＝ゆるい条件
        $high = $this->createMockGroup(1000, null, 1);

        // わざと優先度の低い方を先に並べる
        $customer = $this->createMockCustomer([$low, $high]);
        $order = $this->createMockOrder($customer, 5000);

        $context = $this->createMock(PurchaseContext::class);
        $this->preprocessor->process($order, $context);

        foreach ($order->getShippings() as $Shipping) {
            foreach ($Shipping->getOrderItems() as $Item) {
                if ($Item->getProcessorName() == DeliveryFeePreprocessor::class) {
                    self::assertSame(
                        0,
                        (int) $Item->getQuantity(),
                        '優先度が上のグループの条件で判定されていません'
                    );
                }
            }
        }
    }
}
