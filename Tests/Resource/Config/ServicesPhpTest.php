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
 * タグベースの登録と priority を検証します。
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
     * タグで登録し、priority で順序を決めている。
     */
    public function testタグベースで登録されている(): void
    {

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
     * priority は送料計算と送料無料判定の間でなければならない。
     */
    public function testPriorityが750である(): void
    {

        $container = new ContainerBuilder();
        $container->register('eccube.purchase.flow.shopping', PurchaseFlow::class);

        $loader = new PhpFileLoader($container, new FileLocator(__DIR__.'/../../../Resource/config'));
        $loader->load('services.php');

        $definition = $container->getDefinition(GroupDeliveryFreePreprocessor::class);
        $tags = $definition->getTags();
        $priority = $tags['eccube.item.holder.preprocessor'][0]['priority'];

        // DeliveryFeePreprocessor(800)の後、DeliveryFeeFreeByShippingPreprocessor(700)の前
        self::assertEquals(750, $priority);
        self::assertGreaterThan(700, $priority, 'Priority should be greater than 700 (before DeliveryFeeFreeByShippingPreprocessor)');
        self::assertLessThan(800, $priority, 'Priority should be less than 800 (after DeliveryFeePreprocessor)');
    }
}
