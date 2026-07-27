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

namespace Plugin\CustomerGroupRank44\Tests\Resource\Config;

use Doctrine\Common\Collections\ArrayCollection;
use Eccube\Common\Constant;
use Eccube\Service\PurchaseFlow\Processor\DeliveryFeePreprocessor;
use Eccube\Service\PurchaseFlow\Processor\DeliveryFeeFreeByShippingPreprocessor;
use Eccube\Service\PurchaseFlow\Processor\OrderNoProcessor;
use Eccube\Service\PurchaseFlow\Processor\PaymentChargePreprocessor;
use Eccube\Service\PurchaseFlow\Processor\TaxProcessor;
use Eccube\Service\PurchaseFlow\PurchaseFlow;
use PHPUnit\Framework\TestCase;
use Plugin\CustomerGroupRank44\Service\PurchaseFlow\Processor\GroupDeliveryFreePreprocessor;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;

/**
 * services.php設定ファイルのユニットテスト
 *
 * EC-CUBE 4.2と4.3で異なる設定方法をテストします。
 * - EC-CUBE 4.3+: タグベースの設定（priorityサポート）
 * - EC-CUBE 4.2: ArrayCollectionベースの設定
 */
class ServicesPhpTest extends TestCase
{
    private $servicesPhpPath;

    protected function setUp(): void
    {
        parent::setUp();
        $this->servicesPhpPath = __DIR__.'/../../../Resource/config/services.php';
    }

    public function testServicesPhpが有効なPHPファイルである(): void
    {
        self::assertFileExists($this->servicesPhpPath);

        $callable = require $this->servicesPhpPath;

        self::assertIsCallable($callable);
    }

    /**
     * EC-CUBE 4.3+用テスト: タグベースの設定
     *
     * @group ec-cube-4.3
     */
    public function testEC43ではタグベースの設定が使用される(): void
    {
        if (version_compare(Constant::VERSION, '4.3', '<')) {
            self::markTestSkipped('This test is for EC-CUBE 4.3+');
        }

        $container = new ContainerBuilder();

        $container->register('eccube.purchase.flow.shopping', PurchaseFlow::class);

        $loader = new PhpFileLoader($container, new FileLocator(__DIR__.'/../../../Resource/config'));
        $loader->load('services.php');

        $definition = $container->getDefinition(GroupDeliveryFreePreprocessor::class);

        $tags = $definition->getTags();
        self::assertArrayHasKey('eccube.item.holder.preprocessor', $tags);

        $tagAttributes = $tags['eccube.item.holder.preprocessor'][0];
        self::assertEquals('shopping', $tagAttributes['flow_type']);
        self::assertEquals(750, $tagAttributes['priority']);
    }

    /**
     * EC-CUBE 4.3+用テスト: priorityが正しく設定されている
     *
     * @group ec-cube-4.3
     */
    public function testEC43のPriorityが750である(): void
    {
        if (version_compare(Constant::VERSION, '4.3', '<')) {
            self::markTestSkipped('This test is for EC-CUBE 4.3+');
        }

        $container = new ContainerBuilder();
        $container->register('eccube.purchase.flow.shopping', PurchaseFlow::class);

        $loader = new PhpFileLoader($container, new FileLocator(__DIR__.'/../../../Resource/config'));
        $loader->load('services.php');

        $definition = $container->getDefinition(GroupDeliveryFreePreprocessor::class);
        $tags = $definition->getTags();
        $priority = $tags['eccube.item.holder.preprocessor'][0]['priority'];

        // DeliveryFeePreprocessor(800)の後、DeliveryFeeFreeByShippingPreprocessor(700)の前
        // DeliveryFeePreprocessor(800)の後、DeliveryFeeFreeByShippingPreprocessor(700)の前
        self::assertEquals(750, $priority);
        self::assertGreaterThan(700, $priority, 'Priority should be greater than 700 (before DeliveryFeeFreeByShippingPreprocessor)');
        self::assertLessThan(800, $priority, 'Priority should be less than 800 (after DeliveryFeePreprocessor)');
    }

    /**
     * EC-CUBE 4.2用テスト: ArrayCollectionベースの設定
     *
     * @group ec-cube-4.2
     */
    public function testEC42ではArrayCollectionベースの設定が使用される(): void
    {
        if (version_compare(Constant::VERSION, '4.3', '>=')) {
            self::markTestSkipped('This test is for EC-CUBE 4.2');
        }

        $container = $this->createContainerForEC42();

        $loader = new PhpFileLoader($container, new FileLocator(__DIR__.'/../../../Resource/config'));
        $loader->load('services.php');

        self::assertTrue($container->hasDefinition('eccube.purchase.flow.shopping.holder_preprocessors'));

        $definition = $container->getDefinition('eccube.purchase.flow.shopping.holder_preprocessors');
        self::assertEquals(ArrayCollection::class, $definition->getClass());
    }

    /**
     * EC-CUBE 4.2用テスト: Preprocessorの順序が正しい
     *
     * @group ec-cube-4.2
     */
    public function testEC42のPreprocessor順序が正しい(): void
    {
        if (version_compare(Constant::VERSION, '4.3', '>=')) {
            self::markTestSkipped('This test is for EC-CUBE 4.2');
        }

        $container = $this->createContainerForEC42();

        $loader = new PhpFileLoader($container, new FileLocator(__DIR__.'/../../../Resource/config'));
        $loader->load('services.php');

        $definition = $container->getDefinition('eccube.purchase.flow.shopping.holder_preprocessors');
        $arguments = $definition->getArguments();
        $serviceReferences = $arguments[0];

        $expectedServices = [
            TaxProcessor::class,
            OrderNoProcessor::class,
            DeliveryFeePreprocessor::class,
            GroupDeliveryFreePreprocessor::class,
            DeliveryFeeFreeByShippingPreprocessor::class,
            PaymentChargePreprocessor::class,
            TaxProcessor::class,
        ];

        self::assertCount(count($expectedServices), $serviceReferences);

        foreach ($serviceReferences as $index => $reference) {
            $serviceId = (string) $reference;
            self::assertEquals($expectedServices[$index], $serviceId, "Index {$index} should be {$expectedServices[$index]}");
        }
    }

    /**
     * EC-CUBE 4.2用テスト: GroupDeliveryFreePreprocessorがDeliveryFeePreprocessorの後にある
     *
     * @group ec-cube-4.2
     */
    public function testEC42でGroupDeliveryFreePreprocessorがDeliveryFeePreprocessorの後(): void
    {
        if (version_compare(Constant::VERSION, '4.3', '>=')) {
            self::markTestSkipped('This test is for EC-CUBE 4.2');
        }

        $container = $this->createContainerForEC42();

        $loader = new PhpFileLoader($container, new FileLocator(__DIR__.'/../../../Resource/config'));
        $loader->load('services.php');

        $definition = $container->getDefinition('eccube.purchase.flow.shopping.holder_preprocessors');
        $serviceReferences = $definition->getArguments()[0];

        $deliveryFeeIndex = null;
        $groupDeliveryFreeIndex = null;

        foreach ($serviceReferences as $index => $reference) {
            $serviceId = (string) $reference;
            if ($serviceId === DeliveryFeePreprocessor::class) {
                $deliveryFeeIndex = $index;
            }
            if ($serviceId === GroupDeliveryFreePreprocessor::class) {
                $groupDeliveryFreeIndex = $index;
            }
        }

        self::assertNotNull($deliveryFeeIndex, 'DeliveryFeePreprocessor should exist');
        self::assertNotNull($groupDeliveryFreeIndex, 'GroupDeliveryFreePreprocessor should exist');
        self::assertGreaterThan($deliveryFeeIndex, $groupDeliveryFreeIndex, 'GroupDeliveryFreePreprocessor should be after DeliveryFeePreprocessor');
    }

    /**
     * EC-CUBE 4.2用テスト: GroupDeliveryFreePreprocessorがDeliveryFeeFreeByShippingPreprocessorの前にある
     *
     * @group ec-cube-4.2
     */
    public function testEC42でGroupDeliveryFreePreprocessorがDeliveryFeeFreeByShippingPreprocessorの前(): void
    {
        if (version_compare(Constant::VERSION, '4.3', '>=')) {
            self::markTestSkipped('This test is for EC-CUBE 4.2');
        }

        $container = $this->createContainerForEC42();

        $loader = new PhpFileLoader($container, new FileLocator(__DIR__.'/../../../Resource/config'));
        $loader->load('services.php');

        $definition = $container->getDefinition('eccube.purchase.flow.shopping.holder_preprocessors');
        $serviceReferences = $definition->getArguments()[0];

        $groupDeliveryFreeIndex = null;
        $deliveryFeeFreeByShippingIndex = null;

        foreach ($serviceReferences as $index => $reference) {
            $serviceId = (string) $reference;
            if ($serviceId === GroupDeliveryFreePreprocessor::class) {
                $groupDeliveryFreeIndex = $index;
            }
            if ($serviceId === DeliveryFeeFreeByShippingPreprocessor::class) {
                $deliveryFeeFreeByShippingIndex = $index;
            }
        }

        self::assertNotNull($groupDeliveryFreeIndex, 'GroupDeliveryFreePreprocessor should exist');
        self::assertNotNull($deliveryFeeFreeByShippingIndex, 'DeliveryFeeFreeByShippingPreprocessor should exist');
        self::assertLessThan($deliveryFeeFreeByShippingIndex, $groupDeliveryFreeIndex, 'GroupDeliveryFreePreprocessor should be before DeliveryFeeFreeByShippingPreprocessor');
    }

    /**
     * EC-CUBE 4.2用のコンテナを作成
     */
    private function createContainerForEC42(): ContainerBuilder
    {
        $container = new ContainerBuilder();

        $container->register(TaxProcessor::class)->setPublic(true);
        $container->register(OrderNoProcessor::class)->setPublic(true);
        $container->register(DeliveryFeePreprocessor::class)->setPublic(true);
        $container->register(DeliveryFeeFreeByShippingPreprocessor::class)->setPublic(true);
        $container->register(PaymentChargePreprocessor::class)->setPublic(true);

        return $container;
    }
}
