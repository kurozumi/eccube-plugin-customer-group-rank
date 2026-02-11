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

use Codeception\Actor;
use Codeception\Util\Fixtures;

/**
 * @method void wantToTest($text)
 * @method void wantTo($text)
 * @method void execute($callable)
 * @method void expectTo($prediction)
 * @method void expect($prediction)
 * @method void amGoingTo($argumentation)
 * @method void am($role)
 * @method void lookForwardTo($achieveValue)
 * @method void comment($description)
 */
class AcceptanceTester extends Actor
{
    use _generated\AcceptanceTesterActions;

    /**
     * 管理者としてログイン
     */
    public function loginAsAdmin(string $user = '', string $password = ''): void
    {
        if (!$user || !$password) {
            $account = Fixtures::get('admin_account');
            $user = $account['member'];
            $password = $account['password'];
        }

        $config = Fixtures::get('config');
        $this->amOnPage('/'.$config['eccube_admin_route'].'/');

        $this->submitForm('#form1', [
            'login_id' => $user,
            'password' => $password,
        ]);

        $this->see('ホーム', '.c-pageTitle__title');
    }

    /**
     * 会員としてログイン
     */
    public function loginAsMember(string $email = '', string $password = ''): void
    {
        if (!$email || !$password) {
            $account = Fixtures::get('customer_account');
            $email = $account['email'];
            $password = $account['password'];
        }

        $this->amOnPage('/mypage/login');

        $this->submitForm('#login_mypage', [
            'login_email' => $email,
            'login_pass' => $password,
        ]);

        $this->see('マイページ');
    }

    /**
     * 会員グループ管理ページに移動
     */
    public function goToGroupManagePage(): void
    {
        $config = Fixtures::get('config');
        $this->amOnPage('/'.$config['eccube_admin_route'].'/customer/group');
    }

    /**
     * 会員グループ編集ページに移動
     */
    public function goToGroupEditPage(int $groupId): void
    {
        $config = Fixtures::get('config');
        $this->amOnPage('/'.$config['eccube_admin_route'].'/customer/group/'.$groupId.'/edit');
    }
}
