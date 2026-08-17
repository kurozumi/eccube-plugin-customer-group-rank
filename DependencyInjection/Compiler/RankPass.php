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

namespace Plugin\CustomerGroupRank44\DependencyInjection\Compiler;

use Plugin\CustomerGroupRank44\Service\Rank\Context;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Compiler\PriorityTaggedServiceTrait;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class RankPass implements CompilerPassInterface
{
    use PriorityTaggedServiceTrait;

    public const TAG = 'plugin.customer.group.rank';

    public function process(ContainerBuilder $container): void
    {
        $context = $container->findDefinition(Context::class);

        foreach ($this->findAndSortTaggedServices(self::TAG, $container) as $id) {
            $context->addMethodCall('addRank', [$id]);
        }
    }
}
