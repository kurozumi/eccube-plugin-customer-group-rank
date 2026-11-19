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

namespace Plugin\CustomerGroupRank42\Tests\Security\EventListener;

use Eccube\Tests\EccubeTestCase;
use Plugin\CustomerGroupRank42\Security\EventListener\LoginListener;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Security\Http\SecurityEvents;
use Symfony\Component\Yaml\Yaml;

/**
 * ログインしたときの走る順番。
 *
 * **ランクは他の誰かが会員グループを読む前に当たっていなければならない。**
 * 会員グループは価格・販売可否・配送・支払方法を左右するので、後に回ると
 * その回だけ前のランクで動く。とくに本体の `SecurityListener` が同じイベントで、
 * 保存されていたカートを取り込んで購入フローを流し直す。
 *
 * **順番が狂っても例外は出ない。** 金額と見え方が変わるだけなので、他のテストは
 * 緑のまま通る。だから順番そのものをここで押さえる。
 */
class LoginListenerOrderTest extends EccubeTestCase
{
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
        self::assertNotFalse($core, '本体の '.self::CORE_LISTENER.' が見つからない');
        self::assertLessThan($core, $rank, '並び: '.implode(' → ', $classes));
    }

    /**
     * priority を明示していること。
     *
     * **並びを見るだけでは足りない。** 他がみな既定（0）だと、priority を消しても
     * 登録順でたまたま先に来ることがあり、上のテストは緑のまま通る。だから
     * 宣言（services.yaml のタグ）そのものを見る。
     */
    public function test登録にpriorityを明示している(): void
    {
        $services = Yaml::parseFile(__DIR__.'/../../../Resource/config/services.yaml');
        $tags = $services['services'][LoginListener::class]['tags'] ?? [];

        $found = null;
        foreach ($tags as $tag) {
            if (is_array($tag) && 'kernel.event_listener' === ($tag['name'] ?? null)) {
                $found = $tag;
            }
        }

        self::assertNotNull($found, 'ログインのリスナーとして登録されていない');
        self::assertSame(SecurityEvents::INTERACTIVE_LOGIN, $found['event']);
        self::assertSame('onInteractiveLogin', $found['method']);
        self::assertGreaterThan(0, $found['priority'] ?? 0, 'priority が 0 以下。会員グループを読む側より先に走らせる必要がある');
    }
}
