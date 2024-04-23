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

class LoginListener
{
    /**
     * @var Context
     */
    private Context $context;

    /**
     * @var EntityManagerInterface
     */
    private EntityManagerInterface $entityManager;

    public function __construct(Context $context, EntityManagerInterface $entityManager)
    {
        $this->context = $context;
        $this->entityManager = $entityManager;
    }

    /**
     * @param InteractiveLoginEvent $event
     *
     * @return void
     */
    public function onInteractiveLogin(InteractiveLoginEvent $event): void
    {
        $user = $event->getAuthenticationToken()->getUser();
        if (!$user instanceof Customer) {
            return;
        }

        $this->context->decide($user);
        $this->entityManager->flush();
    }
}
