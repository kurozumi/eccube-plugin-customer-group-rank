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

// EC-CUBEのautoloaderを読み込む
$autoloader = require __DIR__.'/../../../../vendor/autoload.php';

// テスト用の設定を読み込む
\Codeception\Util\Fixtures::add('config', [
    'eccube_admin_route' => getenv('ECCUBE_ADMIN_ROUTE') ?: 'admin',
]);

\Codeception\Util\Fixtures::add('admin_account', [
    'member' => getenv('ADMIN_USER') ?: 'admin',
    'password' => getenv('ADMIN_PASSWORD') ?: 'password',
]);

\Codeception\Util\Fixtures::add('customer_account', [
    'email' => getenv('CUSTOMER_EMAIL') ?: 'test@example.com',
    'password' => getenv('CUSTOMER_PASSWORD') ?: 'password',
]);
