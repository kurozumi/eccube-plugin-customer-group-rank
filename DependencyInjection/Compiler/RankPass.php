<?php

/*
 * This file is part of EC-CUBE
 *
 * Copyright(c) EC-CUBE CO.,LTD. All Rights Reserved.
 *
 * http://www.ec-cube.co.jp/
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Plugin\CustomerGroupRank42\DependencyInjection\Compiler;

use Plugin\CustomerGroupRank42\Service\Rank\Context;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Compiler\PriorityTaggedServiceTrait;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class RankPass implements CompilerPassInterface
{
    use PriorityTaggedServiceTrait;

    public const TAG = 'plugin.customer.group.rank';

    public function process(ContainerBuilder $container)
    {
        $context = $container->findDefinition(Context::class);

        foreach ($this->findAndSortTaggedServices(self::TAG, $container) as $id) {
            $context->addMethodCall('addRank', [$id]);
        }
    }
}
