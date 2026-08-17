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

namespace Plugin\CustomerGroupRank42;

use Doctrine\ORM\EntityManagerInterface;
use Eccube\Entity\Plugin;
use Eccube\Plugin\AbstractPluginManager;
use Psr\Container\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\HttpException;

class PluginManager extends AbstractPluginManager
{
    public function enable(array $meta, ContainerInterface $container): void
    {
        /** @var EntityManagerInterface $entityManager */
        $entityManager = $container->get('doctrine.orm.entity_manager');

        $Plugin = $entityManager
            ->getRepository(Plugin::class)
            ->findOneBy(['code' => 'CustomerGroup42', 'enabled' => true]);

        if (is_null($Plugin)) {
            log_error('会員グループ管理プラグイン for EC-CUBE4が有効化されていないので有効化できません');
            throw new HttpException(400, '会員グループ管理プラグイン for EC-CUBE4が有効化されていないので有効化できません');
        }
    }
}
