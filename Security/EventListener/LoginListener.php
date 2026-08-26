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

namespace Plugin\CustomerGroupRank44\Security\EventListener;

use Doctrine\ORM\EntityManagerInterface;
use Eccube\Entity\Customer;
use Plugin\CustomerGroupRank44\Service\Rank\RankAssignerChain;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Symfony\Component\Security\Http\SecurityEvents;

/**
 * ログインしたら、購入実績からランクを判定して会員グループを当てはめる。
 *
 * **`security.interactive_login` の中でいちばん先に走らなければならない。**
 * 会員グループは価格・販売可否・配送・支払方法を左右するので、ランクを当てる前に
 * 誰かがグループを読むと、その回だけ古いグループで動く。
 *
 * とくに本体の `SecurityListener` が同じイベントで、**保存されていたカートを
 * 取り込んで購入フローを流し直す。** ランクが後だと、そのカートは前のランクの
 * 価格で検証される。
 *
 * priority 10 はそのための値。本体のリスナーはどれも既定（0）なので、
 * それより先に走る。
 * **下げるときは、グループを読む側が本当に後で良いかを確かめる。**
 * 登録の順番は `LoginListenerOrderTest` が見ている。
 *
 * 登録は下の `#[AsEventListener]`。このクラスは `EventSubscriberInterface` を
 * 実装していないので、**`getSubscribedEvents` で grep しても見つからない。**
 */
#[AsEventListener(event: SecurityEvents::INTERACTIVE_LOGIN, method: 'onInteractiveLogin', priority: 10)]
class LoginListener
{
    private RankAssignerChain $chain;

    private EntityManagerInterface $entityManager;

    public function __construct(RankAssignerChain $chain, EntityManagerInterface $entityManager)
    {
        $this->chain = $chain;
        $this->entityManager = $entityManager;
    }

    public function onInteractiveLogin(InteractiveLoginEvent $event): void
    {
        $user = $event->getAuthenticationToken()->getUser();
        if (!$user instanceof Customer) {
            return;
        }

        $this->chain->assign($user);
        $this->entityManager->flush();
    }
}
