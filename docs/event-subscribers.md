# イベント購読の一覧

このプラグインが本体のどこに割り込んでいるか。2本ある。**購読を足したらここも直す。**

## 一覧

| クラス | 購読するもの | 登録の仕方 | すること |
| --- | --- | --- | --- |
| LoginListener | `SecurityEvents::INTERACTIVE_LOGIN` | services.yaml のタグ | ログイン時にランクを判定して会員へ当てはめる |
| Event | `@CustomerGroup44/admin/Customer/Group/edit.twig` | `EventSubscriberInterface` | 会員グループ編集にランクの入力を足す |

**`LoginListener` は `getSubscribedEvents` を持たない。** インターフェースを実装せず、
`services.yaml` の `kernel.event_listener` タグだけで登録している。
**`getSubscribedEvents` で grep すると見つからない**ので、探すときは services.yaml も見る。

## ランクはいちばん先に当てる

`security.interactive_login` には4本ぶら下がっている。

| 順 | priority | 誰 | すること |
| --- | --- | --- | --- |
| 1 | **10** | CustomerGroupRank44（これ） | ランクを判定して会員グループを当てはめ、flush する |
| 2 | 0 | CustomerGroup44 | 見てよい商品IDとカテゴリIDをトークンに控える |
| 3 | 0 | 本体 `LoginHistoryListener` | ログイン履歴（管理者のみ） |
| 4 | 0 | 本体 `SecurityListener` | **保存されていたカートを取り込んで購入フローを流し直す** |

**先に当てないと困るのは4番のため。** 会員グループは価格・販売可否・配送・支払方法を
左右する。ランクが後だと、そのカートは前のランクの価格で検証される。

だから priority 10 を明示している。**下げるときは、グループを読む側が本当に後で
良いかを確かめる。** 順番の検証は `Tests/Security/EventListener/LoginListenerOrderTest.php`
にある（本体のリスナーより先か、CustomerGroup44 より先か）。priority を外すと落ちる。

**順番が狂っても例外は出ない。** 金額と見え方が変わるだけなので、他のテストは緑のまま通る。

## 同じ画面に他のプラグインも足している

`@CustomerGroup44/admin/Customer/Group/edit.twig` には CustomerGroupPrice44 も足す
（卸売価格・割引率の入力）。**足した順が画面の並びになる。**

なお CustomerGroupPrice44 のほうは **JPY のときだけ**足す（率で計算するため）。
このプラグインは通貨を見ていない。
