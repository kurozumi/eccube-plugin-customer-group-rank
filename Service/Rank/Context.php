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

namespace Plugin\CustomerGroupRank42\Service\Rank;

use Eccube\Entity\Customer;

class Context
{
    private array $ranks = [];

    public function addRank(RankInterface $rank): void
    {
        $this->ranks[] = $rank;
    }

    public function decide(Customer $customer): void
    {
        /** @var Rank $rank */
        foreach ($this->ranks as $rank) {
            $rank->decide($customer);
        }
    }
}
