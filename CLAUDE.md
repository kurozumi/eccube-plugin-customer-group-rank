# CLAUDE.md

このファイルは Claude Code がこのリポジトリを理解するためのガイドです。
仕様と使い方は README.md を参照してください。ここでは実装上の注意を扱います。

## プラグイン概要

EC-CUBE 4.4 用。購入実績に基づく会員ランクの自動登録と、会員グループごとの
送料無料条件を提供するアドオン。`CustomerGroup44` に依存する。

## イベント購読の一覧

- `docs/event-subscribers.md` — **本体のどこに割り込んでいるかの一覧。** 同じイベントに
  他のプラグインもぶら下がっている箇所と、priority を明示している箇所をまとめてある。
  購読を足す・動かす前にここを見る。**中身を変えたらこの文書も直す。**

## ディレクトリ構造

```
CustomerGroupRank44/
├── Bundle/                         # バンドル定義（コンパイラパスの登録）
├── DependencyInjection/Compiler/   # ランク判定クラスの収集
├── Entity/                         # Group への EntityExtension
├── Form/Extension/Admin/           # 会員グループ編集画面の入力欄
├── Repository/QueryCustomizer/     # 会員グループ検索への条件追加
├── Resource/config/                # services.yaml / services.php / bundles.php
├── Security/EventListener/         # ログイン時のトリガー
├── Service/Rank/                   # ランク判定
├── Service/PurchaseFlow/Processor/ # 送料無料条件
└── Tests/
```

## 主要クラス

| クラス | 役割 |
|--------|------|
| `Service\Rank\Context` | ランク判定の実行コンテキスト（タグで収集した判定クラスを順に適用） |
| `Service\Rank\Rank` | 既定のランク判定 |
| `Service\Rank\RankInterface` | カスタマイズ用インターフェース |
| `Security\EventListener\LoginListener` | ログイン時に判定を実行 |
| `Repository\QueryCustomizer\GroupSearchCustomizer` | 購入実績による絞り込み条件を追加 |
| `Service\PurchaseFlow\Processor\GroupDeliveryFreePreprocessor` | グループ別の送料無料 |
| `Entity\GroupTrait` | `buyTimes` / `buyTotal` / `deliveryFreeAmount` / `deliveryFreeQuantity` |

## ランク判定の仕組み

`plugin.customer.group.rank` タグの付いたサービスが `RankPass` によって `Context` に
集められ、priority の大きい順に `apply()` される。既定の `Rank` は priority 100。

カスタマイズは `RankInterface` を実装して同じタグを付ける。README にあるとおり
priority を 99 以下にすると既定の後に走る。

判定は `LoginListener` が `security.interactive_login` で起動する。**ログイン時にしか
走らない。** 購入直後にランクを上げたい場合は別途トリガーが必要。

## 実装上の注意

### ランクが管理していないグループを消さないこと

**これが本プラグインで最も重要な注意点。**

以前の `Rank::apply()` は `$customer->getGroups()->clear()` で所属グループを全消去して
いた。購入実績の条件を持たないグループは検索条件（`buyTimes <= x OR buyTotal <= x`）に
**決して一致しない**（NULL 比較のため）ので、会員登録アドオンや承認制アドオン、
管理画面で割り当てたグループがログインのたびに失われ、二度と戻らなかった。

会員グループは会員グループ価格、限定商品・限定カテゴリの閲覧可否、配送方法・
支払方法の選択肢を左右する。影響が広いので、**ランクが管理するグループ
（`buyTimes` か `buyTotal` が設定されているもの）だけを外す**こと。

`Tests/Service/Rank/RankTest` の「手動で割り当てたグループは〜」がこの不変条件を
守っている。

### 所属グループの反復順は並び順を保証しない

送料無料条件は「条件が設定された最初のグループ」で判定するが、
`$customer->getGroups()` の反復順は会員グループの並び順（`sortNo`）と一致しない。
判定前に `sortNo` 昇順へ並べ替えること。会員グループ価格アドオンと同じ考え方。

### 送料無料の適用は数量を 0 にする

`DeliveryFeePreprocessor` が作った送料明細を探し、数量を 0 にすることで無料化する。
明細自体は残す。`services.php` で **`DeliveryFeePreprocessor`（送料計算）の後、
`DeliveryFeeFreeByShippingPreprocessor` の前**に実行されるよう順序を指定している。

順序はタグの priority（750）で決める。`DeliveryFeePreprocessor` が 800、
`DeliveryFeeFreeByShippingPreprocessor` が 700 なので、その間に入る。

### 単体テストはエンティティ拡張のプロキシを読み込むこと

`app/proxy/entity` 以下のプロキシは元のソースツリーを写した階層に置かれるため、
`composer.json` の PSR-4 では解決されない。本体は `Kernel` の起動時に `require_once`
でまとめて読み込んでいる。

そのため**カーネルを起動しない単体テストでは拡張前のクラスが読まれ**、拡張で
足したメソッド（`Customer::hasGroups()` や `Group::getBuyTimes()` など）が存在しない。
スイート全体では他のテストが先にカーネルを起動するので通ってしまい、
**ファイル単体で実行したときだけ落ちる**。

`Tests\EntityProxyLoader` がこれを吸収する。モックでエンティティを扱う
テストクラスでは `setUpBeforeClass()` から呼ぶこと。

### services_test.yaml で autoconfigure を落とさないこと

`Resource/config/services_test.yaml` はテスト環境でのみ読み込まれる
（`Kernel::configureContainer()`）。サービスを再定義すると自動設定が引き継がれない
ため `_defaults` で `autowire` / `autoconfigure` を明示する。

`Context` は現状コンストラクタ引数もタグも持たないため実害は出ないが、増えた瞬間に
テスト環境だけ壊れる。姉妹プラグインでは実際に機能が丸ごと無効になっていた。

### 管理画面の入力欄はテンプレートスニペットで差し込む

`Event.php` が `@CustomerGroup44/admin/Customer/Group/edit.twig` にスニペットを
追加している。親プラグインのテンプレートのパスが変わると表示されなくなるので、
親を更新したときは表示を確認すること。

## テスト

```bash
# EC-CUBE ルートから
bin/test.sh app/Plugin/CustomerGroupRank44/Tests

# プラグイン単体の設定で（APP_ENV=test は自分で渡すこと）
docker compose -p eccube44 exec -e APP_ENV=test ec-cube \
  vendor/bin/phpunit -c app/Plugin/CustomerGroupRank44/phpunit.xml.dist
```

**素の `vendor/bin/phpunit <パス>` で回さない。** 設定を省くとルートの `phpunit.xml` を
拾う。あれは PHPUnit 9 以前（4.2 / 4.3）用で、4.4 の PHPUnit 11 に渡すと**警告を出して
読み飛ばされるだけ**になる。DAMA のロールバックが黙って無効化され、テストが開発用の
データベースを汚し続ける（実際に踏んで、1回の実行で会員が522件残った）。

`bin/test.sh` はコンテナに入っている PHPUnit の実バージョンを見て `phpunit.xml` と
`phpunit.11.xml` を選び分け、選んだあと中身まで検証する。`APP_ENV=test` の受け渡しと
実行ユーザーの固定も引き受ける。以降に出てくる `APP_ENV` と実行ユーザーの注意は、
自分で `vendor/bin/phpunit` を叩くときの話。

`Tests/Web/` 配下は `WebTestCase` 系なので、**`APP_ENV=test` が実行プロセスの
環境変数に入っている必要がある**。`phpunit.xml` の `<server>` 指定は `$_ENV` /
`$_SERVER` の `APP_ENV` に負ける。

**実行ユーザーは一貫させること。** root と www-data を混ぜると `var/cache/<env>` が
root 所有で作られ、次に www-data で実行したときに全ページ 500 になる。起きたら
`chown -R www-data:www-data var/cache var/log` で直る。

## push する前にローカルで検証する

`.githooks/pre-push` が php-cs-fixer・phpstan・phpunit を回す。使うには一度だけ:

```
git config core.hooksPath .githooks
```

- php-cs-fixer は**今回触った PHP ファイルだけ**を見る。元から残っている
  整形ずれで、関係のない push まで止めないため
- phpstan は CI と同じくプラグインのディレクトリごと（Tests も対象）
- phpunit はこのプラグインがローカルで有効なときだけ回す。開発環境に全部を
  同時に入れているとは限らないため

CI は push では**1環境しか回さない**（PHP 8.2 + MySQL）。全環境は
`test-full.yaml` がリリース時・週1・手動で回す。GitHub Actions の実行時間を
push のたびにマトリクス全ジョブぶん使わないため。

急ぐときは `git push --no-verify`。飛ばした変更は CI が受け止める。

## 命名規則

- コミットメッセージ: `type: 説明`（例: `fix: バグ修正`, `docs: ドキュメント更新`）
- ブランチ名: `type/description`（例: `fix/bug-name`）
