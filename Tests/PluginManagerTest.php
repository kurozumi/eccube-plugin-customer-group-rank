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

use Eccube\Tests\EccubeTestCase;
use Plugin\CustomerGroupRank42\Entity\Config;
use Plugin\CustomerGroupRank42\PluginManager;
use Plugin\CustomerGroupRank42\Repository\ConfigRepository;

/**
 * ランクの条件（AND/OR）の既定が、入れ方で分かれること。
 *
 * - 新しくインストールした店 → AND
 * - 以前の版から更新した店（オーナーズストア経由の update()）→ OR
 * - `git pull` で上げた店（update() が走らない）→ 行が無いので OR
 */
class PluginManagerTest extends EccubeTestCase
{
    use RankConditionTrait;

    private function repository(): ConfigRepository
    {
        return $this->entityManager->getRepository(Config::class);
    }

    private function storedConditions(): array
    {
        $this->entityManager->clear();

        return array_map(function (Config $config) {
            return $config->getRankCondition();
        }, $this->repository()->findAll());
    }

    public function test新規インストールではANDになる(): void
    {
        $this->useRankCondition(null);

        (new PluginManager())->install([], static::getContainer());

        self::assertSame([Config::CONDITION_AND], $this->storedConditions());
        self::assertSame(Config::CONDITION_AND, $this->repository()->getRankCondition());
    }

    public function test以前の版からの更新ではORになる(): void
    {
        $this->useRankCondition(null);

        (new PluginManager())->update([], static::getContainer());

        self::assertSame([Config::CONDITION_OR], $this->storedConditions());
        self::assertSame(Config::CONDITION_OR, $this->repository()->getRankCondition());
    }

    public function test更新は店が選んだ値を書き換えない(): void
    {
        $this->useRankCondition(Config::CONDITION_AND);

        (new PluginManager())->update([], static::getContainer());

        self::assertSame([Config::CONDITION_AND], $this->storedConditions());
    }

    public function test行が既にあればインストールでも書き換えない(): void
    {
        $this->useRankCondition(Config::CONDITION_OR);

        (new PluginManager())->install([], static::getContainer());

        self::assertSame([Config::CONDITION_OR], $this->storedConditions());
    }

    /**
     * `git pull` で上げた店は update() も install() も走らず、行が無い。
     */
    public function test行が無ければORとして扱う(): void
    {
        $this->useRankCondition(null);

        self::assertSame([], $this->storedConditions());
        self::assertSame(Config::CONDITION_OR, $this->repository()->getRankCondition());
    }

    public function test列の既定値はOR(): void
    {
        $this->useRankCondition(null);

        // ORM を通さずに行を入れたとき（列の既定値）も OR になること
        $this->entityManager->getConnection()->executeStatement(
            'INSERT INTO '.PluginManager::CONFIG_TABLE.' (rank_condition) VALUES (DEFAULT)'
        );

        self::assertSame([Config::CONDITION_OR], $this->storedConditions());
    }
}
