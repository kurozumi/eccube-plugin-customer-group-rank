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

$loader = require __DIR__.'/../../../../vendor/autoload.php';

$envFile = __DIR__.'/../../../../.env';
if (file_exists($envFile)) {
    (new Symfony\Component\Dotenv\Dotenv())
        ->usePutenv()
        ->bootEnv($envFile);
}

// プラグインのエンティティ拡張は PSR-4 で解決されないため明示的に読み込む。
// 詳細は EntityProxyLoader を参照。
Plugin\CustomerGroupRank44\Tests\EntityProxyLoader::load();
