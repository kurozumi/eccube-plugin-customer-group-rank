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

namespace Plugin\CustomerGroupRank42;

use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Eccube\Entity\Plugin;
use Eccube\Plugin\AbstractPluginManager;
use Plugin\CustomerGroupRank42\Entity\Config;
use Psr\Container\ContainerInterface;
use Symfony\Component\HttpKernel\Exception\HttpException;

class PluginManager extends AbstractPluginManager
{
    /**
     * 設定のテーブル。Entity\Config と同じ名前。
     */
    public const CONFIG_TABLE = 'plg_customer_group_rank_config';

    /**
     * 新しくインストールした店は、購入回数と購入金額の両方を満たしたときだけ
     * ランクを付ける（AND）。
     *
     * install() はスキーマを作った後に呼ばれる。このときエンティティの
     * プロキシはまだ読み込まれていないので、ORM ではなく SQL で入れる。
     */
    public function install(array $meta, ContainerInterface $container): void
    {
        $this->insertConfigIfMissing($this->getConnection($container), Config::CONDITION_AND);
    }

    public function enable(array $meta, ContainerInterface $container): void
    {
        /** @var EntityManagerInterface $entityManager */
        $entityManager = $container->get('doctrine.orm.entity_manager');

        $Plugin = $entityManager
            ->getRepository(Plugin::class)
            ->findOneBy(['code' => 'CustomerGroup42', 'enabled' => true]);

        if (is_null($Plugin)) {
            log_error('会員グループ管理プラグイン for EC-CUBE4が有効化されていないので有効化できません');
            throw new HttpException(400, '会員グループ管理プラグイン for EC-CUBE4が有効化されていないので有効化できません');
        }
    }

    /**
     * 以前の版から上げた店は、今までどおり「どちらか一方（OR）」で判定する。
     *
     * 行が無ければ OR の行を入れる。行が既にあれば（店が選び直した値）触らない。
     * `git pull` で上げた店はこの update() が走らないが、行が無いときは
     * ConfigRepository::getRankCondition() が OR を返すので同じ結果になる。
     */
    public function update(array $meta, ContainerInterface $container): void
    {
        $this->insertConfigIfMissing($this->getConnection($container), Config::CONDITION_OR);
    }

    private function getConnection(ContainerInterface $container): Connection
    {
        /** @var EntityManagerInterface $entityManager */
        $entityManager = $container->get('doctrine.orm.entity_manager');

        return $entityManager->getConnection();
    }

    private function insertConfigIfMissing(Connection $connection, string $rankCondition): void
    {
        $schemaManager = method_exists($connection, 'createSchemaManager')
            ? $connection->createSchemaManager()
            : $connection->getSchemaManager();

        if (!$schemaManager->tablesExist([self::CONFIG_TABLE])) {
            return;
        }

        $count = (int) $connection->fetchOne('SELECT COUNT(*) FROM '.self::CONFIG_TABLE);
        if ($count > 0) {
            return;
        }

        $connection->insert(self::CONFIG_TABLE, ['rank_condition' => $rankCondition]);
    }
}
