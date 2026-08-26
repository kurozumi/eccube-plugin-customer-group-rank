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
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
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
            .'#[AsEventListener] の priority を確かめること（並び: '.implode(' → ', $classes).'）'
        );
    }

    /**
     * 他に誰かが割り込んでも、ランクがいちばん先であること。
     *
     * **会員グループを読むものが後から足されても効くように、位置ではなく
     * 先頭かどうかを見る。** 以前は会員グループ管理の `LoginSubscriber` との
     * 前後を見ていたが、あちらは 2026-08-25 に消えた（誰も読まない値を
     * トークンに控えていただけだった）。
     */
    public function testランクの判定がいちばん先に走る(): void
    {
        $classes = $this->listenerClasses();

        self::assertNotEmpty($classes, 'ログインのリスナーが1つも取れていない');
        self::assertSame(
            LoginListener::class,
            $classes[0],
            'ランクの判定が先頭でない（並び: '.implode(' → ', $classes).'）'
        );
    }

    /**
     * priority を明示していること。
     *
     * **並びを見るだけでは足りない。** 他がみな既定（0）だと、priority を消しても
     * 登録順でたまたま先頭に来るので、上のテストは緑のまま通る。実際に外して
     * 確かめた。「保証されている」と「たまたま正しい」を区別できないと、
     * 意味のない見張りになる。
     *
     * だから宣言そのものを見る。**登録は `#[AsEventListener]` なので、
     * services.yaml ではなく属性を読む。**
     */
    public function test登録にpriorityを明示している(): void
    {
        $attributes = (new \ReflectionClass(LoginListener::class))
            ->getAttributes(AsEventListener::class);

        self::assertCount(1, $attributes, 'ログインのリスナーとして登録されていない');

        /** @var AsEventListener $listener */
        $listener = $attributes[0]->newInstance();

        self::assertSame(SecurityEvents::INTERACTIVE_LOGIN, $listener->event);
        self::assertSame(
            'onInteractiveLogin',
            $listener->method,
            'method を書かないと Symfony が onSecurityInteractiveLogin を探して登録に失敗する'
        );
        self::assertGreaterThan(
            0,
            $listener->priority ?? 0,
            'priority が 0 以下。会員グループを読む側より先に走らせる必要がある'
        );
    }
}
