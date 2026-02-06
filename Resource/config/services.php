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

namespace Symfony\Component\DependencyInjection\Loader\Configurator;

use Eccube\Common\Constant;
use Plugin\CustomerGroupRank42\Service\PurchaseFlow\Processor\GroupDeliveryFreePreprocessor;

return function (ContainerConfigurator $containerConfigurator) {
    $services = $containerConfigurator->services();

    if (version_compare(Constant::VERSION, '4.3', '>=')) {
        // EC-CUBE 4.3以降: タグベースの設定
        $services->set(GroupDeliveryFreePreprocessor::class)
            ->tag('eccube.item.holder.preprocessor', ['flow_type' => 'shopping', 'priority' => 650]);
    } else {
        // EC-CUBE 4.2: 従来の設定
        $services->set(GroupDeliveryFreePreprocessor::class)
            ->tag('eccube.item.holder.preprocessor', ['flow_type' => 'shopping', 'priority' => 650]);
    }
};
