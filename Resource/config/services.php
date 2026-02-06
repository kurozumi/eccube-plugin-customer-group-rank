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
use Plugin\CustomerGroupRank42\Service\PurchaseFlow\Processor\GroupDeliveryFreePreprocessor;

return function (ContainerConfigurator $containerConfigurator) {
    $services = $containerConfigurator->services();

    if (version_compare(Constant::VERSION, '4.3', '>=')) {
        // EC-CUBE 4.3以降: タグベースの設定（priorityサポート）
        $services->set(GroupDeliveryFreePreprocessor::class)
            ->tag('eccube.item.holder.preprocessor', ['flow_type' => 'shopping', 'priority' => 650]);
    } else {
        // EC-CUBE 4.2: ArrayCollectionで順序を指定
        $services->set(GroupDeliveryFreePreprocessor::class);

        $services
            ->set('eccube.purchase.flow.shopping.item_holder_preprocessors')
            ->class(ArrayCollection::class)
            ->args([[
                service('eccube.purchase.flow.item.holder.preprocessor.tax.processor.before'), // 税額の計算(商品明細)
                service('eccube.purchase.flow.item.holder.preprocessor.order.no.processor'), // 注文番号
                service('eccube.purchase.flow.item.holder.preprocessor.delivery.fee.preprocessor'), // 送料
                service(GroupDeliveryFreePreprocessor::class), // 会員グループ送料無料条件
                service('eccube.purchase.flow.item.holder.preprocessor.delivery.fee.free.by.shipping.preprocessor'), // 送料無料
                service('eccube.purchase.flow.item.holder.preprocessor.pyament.charge.preprocessor'), // 手数料
                service('eccube.purchase.flow.item.holder.preprocessor.tax.processor.after'), // 税額の計算(送料・手数料)
            ]]);
    }
};
