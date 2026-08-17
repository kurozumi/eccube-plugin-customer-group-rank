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

namespace Plugin\CustomerGroupRank44\Tests;

/**
 * エンティティ拡張（@EntityExtension）のプロキシを読み込む。
 *
 * app/proxy/entity 以下のプロキシは元のソースツリーをそのまま写した階層に
 * 置かれるため（例: app/proxy/entity/src/Eccube/Entity/Customer.php）、
 * composer.json の PSR-4 では解決されない。本体は Kernel の起動時に
 * require_once でまとめて読み込んでいる。
 *
 * そのためカーネルを起動しない単体テストでは拡張前のクラスが読まれ、
 * 拡張で足したメソッド（Customer::hasGroups() や Group::getBuyTimes() など）が
 * 存在しない状態になる。テストを単体で実行したときだけ「モックできない
 * メソッド」で落ち、スイート全体では他のテストが先にカーネルを起動するので
 * 通る、という気付きにくい形になる。
 */
final class EntityProxyLoader
{
    /**
     * @return void
     */
    public static function load(): void
    {
        static $loaded = false;

        if ($loaded) {
            return;
        }
        $loaded = true;

        // 拡張前のクラスが先に読まれていた場合にプロキシを重ねると、
        // クラス重複で致命的エラーになるため何もしない。
        if (
            class_exists(\Eccube\Entity\Customer::class, false)
            || class_exists(\Plugin\CustomerGroup44\Entity\Group::class, false)
        ) {
            return;
        }

        $dir = __DIR__.'/../../../proxy/entity';
        if (!is_dir($dir)) {
            return;
        }

        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS)
        );

        foreach ($files as $file) {
            if ($file->isFile() && 'php' === $file->getExtension()) {
                require_once $file->getRealPath();
            }
        }
    }
}
