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
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

/**
 * ランクの当て方。
 *
 * `Context` が priority の降順に**すべて呼ぶ。** 途中で打ち切らないので、
 * 後から呼ばれたものが前の結果を上書きしうる。
 *
 * 実装すればタグは自動で付く（下の `#[AutoconfigureTag]`）。集めるのは
 * `Context` の `#[AutowireIterator]`。**services.yaml に書く必要は無い。**
 */
#[AutoconfigureTag(RankAssignerInterface::TAG)]
interface RankAssignerInterface
{
    /** 実装を集めるタグ */
    public const TAG = 'plugin.customer.group.rank';

    public function assign(Customer $customer): void;
}
