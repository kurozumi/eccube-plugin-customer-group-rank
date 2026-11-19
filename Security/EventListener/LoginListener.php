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

namespace Plugin\CustomerGroupRank42\Security\EventListener;

use Doctrine\ORM\EntityManagerInterface;
use Eccube\Entity\Customer;
use Plugin\CustomerGroupRank42\Service\Rank\Context;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;

/**
 * ログインしたら、購入実績からランクを判定して会員グループを当てはめる。
 *
 * **`security.interactive_login` の中で先に走らなければならない。** 会員グループは
 * 価格・販売可否・配送・支払方法を左右するので、ランクを当てる前に誰かが
 * グループを読むと、その回だけ古いグループで動く。本体の `SecurityListener` が
 * 同じイベントで保存されていたカートを取り込んで購入フローを流し直すため、
 * priority 10 で登録している（Resource/config/services.yaml のタグ。
 * 順番の検証は LoginListenerOrderTest）。
 */
class LoginListener
{
    private Context $context;

    private EntityManagerInterface $entityManager;

    public function __construct(Context $context, EntityManagerInterface $entityManager)
    {
        $this->context = $context;
        $this->entityManager = $entityManager;
    }

    public function onInteractiveLogin(InteractiveLoginEvent $event): void
    {
        $user = $event->getAuthenticationToken()->getUser();
        if (!$user instanceof Customer) {
            return;
        }

        $this->context->apply($user);
        $this->entityManager->flush();
    }
}
