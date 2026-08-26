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

namespace Plugin\CustomerGroupRank44\Tests\Security\EventListener;

use Doctrine\ORM\EntityManagerInterface;
use Eccube\Entity\Customer;
use Eccube\Entity\Member;
use PHPUnit\Framework\TestCase;
use Plugin\CustomerGroupRank44\Security\EventListener\LoginListener;
use Plugin\CustomerGroupRank44\Service\Rank\RankAssignerChain;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;

class LoginListenerTest extends TestCase
{
    public function testCustomerログイン時にランク判定が実行される(): void
    {
        $customer = $this->createMock(Customer::class);

        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($customer);

        $event = new InteractiveLoginEvent(new Request(), $token);

        $chain = $this->createMock(RankAssignerChain::class);
        $chain->expects(self::once())->method('assign')->with($customer);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::once())->method('flush');

        $listener = new LoginListener($chain, $entityManager);
        $listener->onInteractiveLogin($event);
    }

    public function testCustomer以外のログイン時はランク判定が実行されない(): void
    {
        $member = $this->createMock(Member::class);

        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($member);

        $event = new InteractiveLoginEvent(new Request(), $token);

        $chain = $this->createMock(RankAssignerChain::class);
        $chain->expects(self::never())->method('assign');

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::never())->method('flush');

        $listener = new LoginListener($chain, $entityManager);
        $listener->onInteractiveLogin($event);
    }
}
