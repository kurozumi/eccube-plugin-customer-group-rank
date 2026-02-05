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

namespace Plugin\CustomerGroupRank42\Tests\Security\EventListener;

use Doctrine\ORM\EntityManagerInterface;
use Eccube\Entity\Customer;
use Eccube\Entity\Member;
use PHPUnit\Framework\TestCase;
use Plugin\CustomerGroupRank42\Security\EventListener\LoginListener;
use Plugin\CustomerGroupRank42\Service\Rank\Context;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;

class LoginListenerTest extends TestCase
{
    public function testCustomerログイン時にランク判定が実行される(): void
    {
        $customer = $this->createMock(Customer::class);

        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($customer);

        $event = $this->createMock(InteractiveLoginEvent::class);
        $event->method('getAuthenticationToken')->willReturn($token);

        $context = $this->createMock(Context::class);
        $context->expects(self::once())->method('decide')->with($customer);

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::once())->method('flush');

        $listener = new LoginListener($context, $entityManager);
        $listener->onInteractiveLogin($event);
    }

    public function testCustomer以外のログイン時はランク判定が実行されない(): void
    {
        $member = $this->createMock(Member::class);

        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($member);

        $event = $this->createMock(InteractiveLoginEvent::class);
        $event->method('getAuthenticationToken')->willReturn($token);

        $context = $this->createMock(Context::class);
        $context->expects(self::never())->method('decide');

        $entityManager = $this->createMock(EntityManagerInterface::class);
        $entityManager->expects(self::never())->method('flush');

        $listener = new LoginListener($context, $entityManager);
        $listener->onInteractiveLogin($event);
    }
}
