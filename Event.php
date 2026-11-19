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

use Eccube\Event\TemplateEvent;
use Plugin\CustomerGroupRank42\Entity\Config;
use Plugin\CustomerGroupRank42\Repository\ConfigRepository;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class Event implements EventSubscriberInterface
{
    /**
     * @var ConfigRepository|null
     */
    private $configRepository;

    /**
     * 会員グループ編集画面に「ランクの条件（AND/OR）」を出すために読む。
     * コンストラクタは以前の版のまま（引数なし）。
     *
     * @required
     */
    public function setConfigRepository(ConfigRepository $configRepository): void
    {
        $this->configRepository = $configRepository;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            '@CustomerGroup42/admin/Customer/Group/edit.twig' => 'onTemplateAdminCustomerGroupEdit',
        ];
    }

    public function onTemplateAdminCustomerGroupEdit(TemplateEvent $event): void
    {
        $event->setParameter(
            'customer_group_rank_condition',
            null === $this->configRepository ? Config::CONDITION_OR : $this->configRepository->getRankCondition()
        );
        $event->addSnippet('@CustomerGroupRank42/admin/Customer/Group/edit.twig');
    }
}
