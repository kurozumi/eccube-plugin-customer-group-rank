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
use Eccube\Service\PurchaseFlow\Processor\DeliveryFeePreprocessor;
use Eccube\Service\PurchaseFlow\Processor\DeliveryFeeFreeByShippingPreprocessor;
use Eccube\Service\PurchaseFlow\PurchaseFlow;
use Eccube\Tests\EccubeTestCase;
use Plugin\CustomerGroupRank44\Service\PurchaseFlow\Processor\GroupDeliveryFreePreprocessor;

/**
 * GroupDeliveryFreePreprocessorの統合テスト
 *
 * 実際のEC-CUBEコンテナでPurchaseFlowの設定を検証します。
 * EC-CUBE 4.2と4.3の両方で動作します。
 *
 * Note: このテストはプラグインがインストールされている環境で実行する必要があります。
 */
class GroupDeliveryFreePreprocessorWebTest extends EccubeTestCase
{
    /**
     * @var PurchaseFlow
     */
    private $shoppingFlow;

    protected function setUp(): void
    {
        parent::setUp();

        // プラグインがインストールされているか確認
        if (!static::getContainer()->has(GroupDeliveryFreePreprocessor::class)) {
            self::markTestSkipped('CustomerGroupRank44 plugin is not installed');
        }

        $this->shoppingFlow = static::getContainer()->get('eccube.purchase.flow.shopping');
    }

    public function testGroupDeliveryFreePreprocessorがコンテナに登録されている(): void
    {
        self::assertTrue(
            static::getContainer()->has(GroupDeliveryFreePreprocessor::class),
            'GroupDeliveryFreePreprocessor should be registered in container'
        );
    }

    public function testGroupDeliveryFreePreprocessorがショッピングフローに登録されている(): void
    {
        $preprocessors = $this->getItemHolderPreprocessors();

        $found = false;
        foreach ($preprocessors as $preprocessor) {
            if ($preprocessor instanceof GroupDeliveryFreePreprocessor) {
                $found = true;
                break;
            }
        }

        self::assertTrue($found, 'GroupDeliveryFreePreprocessor should be registered in shopping flow');
    }

    public function testGroupDeliveryFreePreprocessorがDeliveryFeePreprocessorの後に実行される(): void
    {
        $preprocessors = $this->getItemHolderPreprocessors();

        $deliveryFeeIndex = null;
        $groupDeliveryFreeIndex = null;

        foreach ($preprocessors as $index => $preprocessor) {
            if ($preprocessor instanceof DeliveryFeePreprocessor) {
                $deliveryFeeIndex = $index;
            }
            if ($preprocessor instanceof GroupDeliveryFreePreprocessor) {
                $groupDeliveryFreeIndex = $index;
            }
        }

        self::assertNotNull($deliveryFeeIndex, 'DeliveryFeePreprocessor should be registered');
        self::assertNotNull($groupDeliveryFreeIndex, 'GroupDeliveryFreePreprocessor should be registered');
        self::assertGreaterThan(
            $deliveryFeeIndex,
            $groupDeliveryFreeIndex,
            'GroupDeliveryFreePreprocessor should run after DeliveryFeePreprocessor'
        );
    }

    public function testGroupDeliveryFreePreprocessorがDeliveryFeeFreeByShippingPreprocessorの前に実行される(): void
    {
        $preprocessors = $this->getItemHolderPreprocessors();

        $groupDeliveryFreeIndex = null;
        $deliveryFeeFreeByShippingIndex = null;

        foreach ($preprocessors as $index => $preprocessor) {
            if ($preprocessor instanceof GroupDeliveryFreePreprocessor) {
                $groupDeliveryFreeIndex = $index;
            }
            if ($preprocessor instanceof DeliveryFeeFreeByShippingPreprocessor) {
                $deliveryFeeFreeByShippingIndex = $index;
            }
        }

        self::assertNotNull($groupDeliveryFreeIndex, 'GroupDeliveryFreePreprocessor should be registered');
        self::assertNotNull($deliveryFeeFreeByShippingIndex, 'DeliveryFeeFreeByShippingPreprocessor should be registered');
        self::assertLessThan(
            $deliveryFeeFreeByShippingIndex,
            $groupDeliveryFreeIndex,
            'GroupDeliveryFreePreprocessor should run before DeliveryFeeFreeByShippingPreprocessor'
        );
    }

    public function testPreprocessorの順序が正しい(): void
    {
        $preprocessors = $this->getItemHolderPreprocessors();

        $classes = [];
        foreach ($preprocessors as $preprocessor) {
            $classes[] = get_class($preprocessor);
        }

        // GroupDeliveryFreePreprocessorの位置を確認
        $deliveryFeePos = array_search(DeliveryFeePreprocessor::class, $classes, true);
        $groupDeliveryFreePos = array_search(GroupDeliveryFreePreprocessor::class, $classes, true);
        $deliveryFeeFreePos = array_search(DeliveryFeeFreeByShippingPreprocessor::class, $classes, true);

        self::assertNotFalse($deliveryFeePos, 'DeliveryFeePreprocessor should exist');
        self::assertNotFalse($groupDeliveryFreePos, 'GroupDeliveryFreePreprocessor should exist');
        self::assertNotFalse($deliveryFeeFreePos, 'DeliveryFeeFreeByShippingPreprocessor should exist');

        // 順序: DeliveryFee < GroupDeliveryFree < DeliveryFeeFree
        self::assertLessThan($groupDeliveryFreePos, $deliveryFeePos, 'DeliveryFeePreprocessor should be before GroupDeliveryFreePreprocessor');
        self::assertLessThan($deliveryFeeFreePos, $groupDeliveryFreePos, 'GroupDeliveryFreePreprocessor should be before DeliveryFeeFreeByShippingPreprocessor');
    }

    private function getItemHolderPreprocessors(): ArrayCollection
    {
        $reflection = new \ReflectionObject($this->shoppingFlow);
        $property = $reflection->getProperty('itemHolderPreprocessors');
        $property->setAccessible(true);

        return $property->getValue($this->shoppingFlow);
    }
}
