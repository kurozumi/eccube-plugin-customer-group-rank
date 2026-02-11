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

/**
 * 会員グループランク管理のE2Eテスト
 *
 * @group plugin
 * @group customer-group-rank
 */
class GroupRankCest
{
    public function _before(AcceptanceTester $I): void
    {
        $I->loginAsAdmin();
    }

    public function 会員グループ一覧ページが表示される(AcceptanceTester $I): void
    {
        $I->wantTo('会員グループ一覧ページが表示されることを確認する');

        $I->goToGroupManagePage();
        $I->see('会員グループ管理');
    }

    public function 会員グループにランク条件を設定できる(AcceptanceTester $I): void
    {
        $I->wantTo('会員グループにランク条件（購入回数・購入金額）を設定できることを確認する');

        // 会員グループ編集ページに移動（ID=1のグループ）
        $I->goToGroupEditPage(1);

        // 購入回数を入力
        $I->fillField('input[name="group[buyTimes]"]', '5');

        // 購入金額を入力
        $I->fillField('input[name="group[buyTotal]"]', '10000');

        // 保存
        $I->click('登録');

        // 成功メッセージを確認
        $I->see('保存しました');
    }

    public function 会員グループに送料無料条件を設定できる(AcceptanceTester $I): void
    {
        $I->wantTo('会員グループに送料無料条件を設定できることを確認する');

        // 会員グループ編集ページに移動（ID=1のグループ）
        $I->goToGroupEditPage(1);

        // 送料無料条件（税込み金額）を入力
        $I->fillField('input[name="group[deliveryFreeAmount]"]', '5000');

        // 送料無料条件（数量）を入力
        $I->fillField('input[name="group[deliveryFreeQuantity]"]', '3');

        // 保存
        $I->click('登録');

        // 成功メッセージを確認
        $I->see('保存しました');
    }
}
