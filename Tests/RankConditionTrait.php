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

namespace Plugin\CustomerGroupRank42\Tests;

use Plugin\CustomerGroupRank42\Entity\Config;
use Plugin\CustomerGroupRank42\Repository\ConfigRepository;

/**
 * ランクの条件（AND/OR）をテストの中で決める。
 *
 * CI はプラグインを install するので AND の行が入り、スタックは update 相当
 * （行なし）なので OR になる。**環境で既定が違うので、判定を見るテストは
 * 必ずどちらかに固定する。** DAMA がロールバックするので後始末は要らない。
 */
trait RankConditionTrait
{
    protected function useRankCondition(?string $rankCondition): void
    {
        /** @var ConfigRepository $repository */
        $repository = $this->entityManager->getRepository(Config::class);
        foreach ($repository->findAll() as $config) {
            $this->entityManager->remove($config);
        }
        $this->entityManager->flush();

        if (null === $rankCondition) {
            return; // 行が無い状態（以前の版から git pull で上げた店）
        }

        $repository->getOrCreate()->setRankCondition($rankCondition);
        $this->entityManager->flush();
    }
}
