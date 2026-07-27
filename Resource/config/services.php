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

use Doctrine\Common\Collections\ArrayCollection;
use Eccube\Common\Constant;
use Plugin\CustomerGroupRank44\Service\PurchaseFlow\Processor\GroupDeliveryFreePreprocessor;

return function (ContainerConfigurator $containerConfigurator) {
    $services = $containerConfigurator->services();

    if (version_compare(Constant::VERSION, '4.3', '>=')) {
        // EC-CUBE 4.3以降: タグベースの設定（priorityサポート）
        // DeliveryFeePreprocessor(800)の後、DeliveryFeeFreeByShippingPreprocessor(700)の前に実行
        $services->set(GroupDeliveryFreePreprocessor::class)
            ->tag('eccube.item.holder.preprocessor', ['flow_type' => 'shopping', 'priority' => 750]);
    } else {
        // EC-CUBE 4.2: ArrayCollectionで順序を指定
        $services->set(GroupDeliveryFreePreprocessor::class);

        $services
            ->set('eccube.purchase.flow.shopping.holder_preprocessors')
            ->class(ArrayCollection::class)
            ->args([[
                service('Eccube\Service\PurchaseFlow\Processor\TaxProcessor'), // 税額の計算(商品明細)
                service('Eccube\Service\PurchaseFlow\Processor\OrderNoProcessor'),
                service('Eccube\Service\PurchaseFlow\Processor\DeliveryFeePreprocessor'),
                service(GroupDeliveryFreePreprocessor::class), // 会員グループ送料無料条件
                service('Eccube\Service\PurchaseFlow\Processor\DeliveryFeeFreeByShippingPreprocessor'),
                service('Eccube\Service\PurchaseFlow\Processor\PaymentChargePreprocessor'),
                service('Eccube\Service\PurchaseFlow\Processor\TaxProcessor'), // 税額の計算(送料・手数料)
            ]]);
    }
};
