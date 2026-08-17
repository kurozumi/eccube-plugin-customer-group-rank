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

namespace Plugin\CustomerGroupRank42\Tests\DependencyInjection\Compiler;

use Eccube\Entity\Customer;
use PHPUnit\Framework\TestCase;
use Plugin\CustomerGroupRank42\DependencyInjection\Compiler\RankPass;
use Plugin\CustomerGroupRank42\Service\Rank\Context;
use Plugin\CustomerGroupRank42\Service\Rank\RankInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class RankPassTest extends TestCase
{
    public function testタグ付きサービスがContextに登録される(): void
    {
        $container = new ContainerBuilder();
        $container->register(Context::class)
            ->setPublic(true);

        $container->register(TestRank::class)
            ->addTag(RankPass::TAG);

        $container->addCompilerPass(new RankPass());
        $container->compile();

        $context = $container->get(Context::class);
        $reflection = new \ReflectionObject($context);
        $prop = $reflection->getProperty('ranks');
        $prop->setAccessible(true);
        $ranks = $prop->getValue($context);

        self::assertCount(1, $ranks);
    }

    public function testタグ付きサービスが未登録の場合はranksが空(): void
    {
        $container = new ContainerBuilder();
        $container->register(Context::class)
            ->setPublic(true);

        $container->addCompilerPass(new RankPass());
        $container->compile();

        $context = $container->get(Context::class);
        $reflection = new \ReflectionObject($context);
        $prop = $reflection->getProperty('ranks');
        $prop->setAccessible(true);
        $ranks = $prop->getValue($context);

        self::assertCount(0, $ranks);
    }
}

class TestRank implements RankInterface
{
    public function apply(Customer $customer): void
    {
    }
}
