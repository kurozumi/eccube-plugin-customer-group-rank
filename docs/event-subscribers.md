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

## ログインの2本は順番に意味がある

`security.interactive_login` には CustomerGroup44 の `LoginSubscriber` もいる。

| 順 | プラグイン | すること |
| --- | --- | --- |
| 1 | CustomerGroupRank44（これ） | ランクを判定して会員グループを当てはめ、flush する |
| 2 | CustomerGroup44 | 見てよい商品IDとカテゴリIDをトークンに控える |

**この順でないと困る。** ランクで会員グループが変わったのに、控えるほうが先に走ると、
そのセッションだけ古いIDを持つ。実機ではこの順に走っている
（`debug:event-dispatcher` で確認）。

ただし **priority はどちらも0で、登録順に頼っている。** ここを動かすなら実機で確かめる。

## 同じ画面に他のプラグインも足している

`@CustomerGroup44/admin/Customer/Group/edit.twig` には CustomerGroupPrice44 も足す
（卸売価格・割引率の入力）。**足した順が画面の並びになる。**

なお CustomerGroupPrice44 のほうは **JPY のときだけ**足す（率で計算するため）。
このプラグインは通貨を見ていない。
