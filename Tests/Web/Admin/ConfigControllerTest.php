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

namespace Plugin\CustomerGroupRank42\Tests\Web\Admin;

use Eccube\Tests\Web\Admin\AbstractAdminWebTestCase;
use Plugin\CustomerGroupRank42\Entity\Config;
use Plugin\CustomerGroupRank42\Repository\ConfigRepository;
use Plugin\CustomerGroupRank42\Tests\RankConditionTrait;

class ConfigControllerTest extends AbstractAdminWebTestCase
{
    use RankConditionTrait;

    public function test設定画面に今の条件が選ばれて出る(): void
    {
        $this->useRankCondition(null);

        $crawler = $this->client->request('GET', $this->generateUrl('customer_group_rank42_admin_config'));

        self::assertTrue($this->client->getResponse()->isSuccessful());
        // 行が無い店（以前の版から上げた店）は OR が選ばれている
        self::assertSame(
            Config::CONDITION_OR,
            $crawler->filter('input[name="config[rankCondition]"][checked]')->attr('value')
        );
    }

    public function testANDを選んで保存できる(): void
    {
        $this->useRankCondition(Config::CONDITION_OR);

        $crawler = $this->client->request('GET', $this->generateUrl('customer_group_rank42_admin_config'));
        $token = $crawler->filter('#config__token')->attr('value');

        $this->client->request('POST', $this->generateUrl('customer_group_rank42_admin_config'), [
            'config' => [
                '_token' => $token,
                'rankCondition' => Config::CONDITION_AND,
            ],
        ]);

        self::assertTrue($this->client->getResponse()->isRedirect($this->generateUrl('customer_group_rank42_admin_config')));

        $this->entityManager->clear();
        /** @var ConfigRepository $repository */
        $repository = $this->entityManager->getRepository(Config::class);
        self::assertSame(Config::CONDITION_AND, $repository->getRankCondition());
    }

    public function test選択肢に無い値は保存しない(): void
    {
        $this->useRankCondition(Config::CONDITION_OR);

        $crawler = $this->client->request('GET', $this->generateUrl('customer_group_rank42_admin_config'));
        $token = $crawler->filter('#config__token')->attr('value');

        $this->client->request('POST', $this->generateUrl('customer_group_rank42_admin_config'), [
            'config' => [
                '_token' => $token,
                'rankCondition' => 'xor',
            ],
        ]);

        self::assertFalse($this->client->getResponse()->isRedirect());

        $this->entityManager->clear();
        /** @var ConfigRepository $repository */
        $repository = $this->entityManager->getRepository(Config::class);
        self::assertSame(Config::CONDITION_OR, $repository->getRankCondition());
    }

    /**
     * 会員グループ編集画面に、いまの条件と設定への入口が出る。
     */
    public function test会員グループ編集画面に今の条件が出る(): void
    {
        $this->useRankCondition(Config::CONDITION_AND);

        $crawler = $this->client->request('GET', $this->generateUrl('admin_customer_group_new'));

        self::assertTrue($this->client->getResponse()->isSuccessful());
        $note = $crawler->filter('#customer_group_rank_condition');
        self::assertCount(1, $note);
        self::assertStringContainsString('AND', $note->text());
        // テンプレートは url()（絶対 URL）で出すので、末尾で見る
        self::assertCount(1, $note->filter('a[href$="'.$this->generateUrl('customer_group_rank42_admin_config').'"]'));
    }

    public function test会員グループ編集画面ORの店ではORと出る(): void
    {
        $this->useRankCondition(null);

        $crawler = $this->client->request('GET', $this->generateUrl('admin_customer_group_new'));

        self::assertStringContainsString('OR', $crawler->filter('#customer_group_rank_condition')->text());
    }
}
