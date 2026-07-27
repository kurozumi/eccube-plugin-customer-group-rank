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

namespace Plugin\CustomerGroupRank44\Bundle;

use Plugin\CustomerGroupRank44\DependencyInjection\Compiler\RankPass;
use Plugin\CustomerGroupRank44\Service\Rank\RankInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class CustomerGroupRankBundle extends Bundle
{
    public function build(ContainerBuilder $container): void
    {
        parent::build($container);

        $container->registerForAutoconfiguration(RankInterface::class)
            ->addTag(RankPass::TAG);

        $container->addCompilerPass(new RankPass());
    }
}
