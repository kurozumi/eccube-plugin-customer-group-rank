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

namespace Plugin\CustomerGroupRank44\Service\Rank;

use Eccube\Entity\Customer;
use Symfony\Component\DependencyInjection\Attribute\AutowireIterator;

/**
 * 会員にランクを当てる。
 *
 * 実装を priority の降順に**すべて呼ぶ。** 集めるのは `#[AutowireIterator]` で、
 * **CompilerPass は要らない。**
 */
class Context
{
    /**
     * @param iterable<RankInterface> $ranks priority の降順で渡される
     */
    public function __construct(
        #[AutowireIterator(RankInterface::TAG)]
        private readonly iterable $ranks = [],
    ) {
    }

    public function apply(Customer $customer): void
    {
        /** @var Rank $rank */
        foreach ($this->ranks as $rank) {
            $rank->apply($customer);
        }
    }
}
