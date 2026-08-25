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

namespace Plugin\CustomerGroupRank44\Tests\Security\EventListener;

use Eccube\Tests\EccubeTestCase;
use Plugin\CustomerGroupRank44\Security\EventListener\LoginListener;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Security\Http\SecurityEvents;

/**
 * ログインしたときの走る順番。
 *
 * **ランクは他の誰かが会員グループを読む前に当たっていなければならない。**
 * 会員グループは価格・販売可否・配送・支払方法を左右するので、後に回ると
 * その回だけ前のランクで動く。
 *
 * とくに本体の `SecurityListener` が同じイベントで、保存されていたカートを
 * 取り込んで購入フローを流し直す。ここが先だと、カートは前のランクの価格で
 * 検証される。
 *
 * **順番が狂っても例外は出ない。** 金額と見え方が変わるだけなので、他のテストは
 * 緑のまま通る。だから順番そのものをここで押さえる。
 */
class LoginListenerOrderTest extends EccubeTestCase
{
    /**
     * 本体のリスナー。カートを取り込んで購入フローを流し直す。
     */
    private const CORE_LISTENER = 'Eccube\EventListener\SecurityListener';

    /**
     * @return string[] 呼ばれる順にクラス名を並べたもの
     */
    private function listenerClasses(): array
    {
        /** @var EventDispatcherInterface $dispatcher */
        $dispatcher = static::getContainer()->get('event_dispatcher');

        $classes = [];
        foreach ($dispatcher->getListeners(SecurityEvents::INTERACTIVE_LOGIN) as $listener) {
            // [オブジェクト, メソッド名] の形で返る
            if (is_array($listener) && is_object($listener[0])) {
                $classes[] = get_class($listener[0]);
            }
        }

        return $classes;
    }

    public function testランクの判定が本体のカート取り込みより先に走る(): void
    {
        $classes = $this->listenerClasses();

        $rank = array_search(LoginListener::class, $classes, true);
        $core = array_search(self::CORE_LISTENER, $classes, true);

        self::assertNotFalse($rank, 'ランクを当てるリスナーが登録されていない');
        self::assertNotFalse($core, '本体の '.self::CORE_LISTENER.' が見つからない。本体側が変わった可能性がある');

        self::assertLessThan(
            $core,
            $rank,
            'ランクの判定が本体のカート取り込みより後に走る。'
            .'services.yaml の priority を確かめること（並び: '.implode(' → ', $classes).'）'
        );
    }

    /**
     * 会員グループ管理の LoginSubscriber より先に走ること。
     *
     * あちらは会員が見てよい商品とカテゴリを、**会員グループから引いて**
     * トークンに控える。ランクが後だと、その回だけ前のランクの結果を控える。
     */
    public function test会員グループ管理のリスナーより先に走る(): void
    {
        $classes = $this->listenerClasses();

        $subscriber = 'Plugin\CustomerGroup44\Security\EventSubscriber\LoginSubscriber';
        $target = array_search($subscriber, $classes, true);

        if (false === $target) {
            self::markTestSkipped('会員グループ管理の LoginSubscriber が登録されていない');
        }

        $rank = array_search(LoginListener::class, $classes, true);
        self::assertNotFalse($rank, 'ランクを当てるリスナーが登録されていない');

        self::assertLessThan(
            $target,
            $rank,
            'ランクの判定が会員グループ管理のリスナーより後に走る（並び: '.implode(' → ', $classes).'）'
        );
    }
}
