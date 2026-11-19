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

namespace Plugin\CustomerGroupRank42\Repository;

use Doctrine\Persistence\ManagerRegistry;
use Eccube\Repository\AbstractRepository;
use Plugin\CustomerGroupRank42\Entity\Config;

class ConfigRepository extends AbstractRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Config::class);
    }

    /**
     * 設定の行。無ければ null。
     */
    public function findConfig(): ?Config
    {
        return $this->findOneBy([], ['id' => 'ASC']);
    }

    /**
     * 設定の行。無ければ OR の行を作って返す（flush はしない）。
     */
    public function getOrCreate(): Config
    {
        $config = $this->findConfig();
        if (null === $config) {
            $config = new Config();
            $config->setRankCondition(Config::CONDITION_OR);
            $this->getEntityManager()->persist($config);
        }

        return $config;
    }

    /**
     * いま効いている条件の結合（Config::CONDITION_OR / CONDITION_AND）。
     *
     * **行が無ければ OR。** 以前の版から `git pull` で上げた店は行を持たないので、
     * ここで今までどおりの判定になる。
     *
     * **テーブルがまだ無くても OR を返す。** ファイルだけ新しくして
     * `eccube:plugin:schema-update` を打っていない店で、ログインのたびに
     * SQL エラーで 500 にしないため。PostgreSQL は失敗した文でトランザクション
     * ごと使えなくなるので、例外を捕まえるのではなく先にテーブルの有無を見る。
     */
    public function getRankCondition(): string
    {
        if (!$this->hasTable()) {
            return Config::CONDITION_OR;
        }

        $config = $this->findConfig();

        return null === $config ? Config::CONDITION_OR : $config->getRankCondition();
    }

    public function hasTable(): bool
    {
        $connection = $this->getEntityManager()->getConnection();
        $schemaManager = method_exists($connection, 'createSchemaManager')
            ? $connection->createSchemaManager()
            : $connection->getSchemaManager();

        return $schemaManager->tablesExist([$this->getClassMetadata()->getTableName()]);
    }
}
