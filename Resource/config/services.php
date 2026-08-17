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

use Plugin\CustomerGroupRank44\Service\PurchaseFlow\Processor\GroupDeliveryFreePreprocessor;

return function (ContainerConfigurator $containerConfigurator) {
    $services = $containerConfigurator->services();

    // 送料計算(DeliveryFeePreprocessor, 800)の後、
    // DeliveryFeeFreeByShippingPreprocessor(700)の前に実行する。
    $services->set(GroupDeliveryFreePreprocessor::class)
        ->tag('eccube.item.holder.preprocessor', ['flow_type' => 'shopping', 'priority' => 750]);
};
