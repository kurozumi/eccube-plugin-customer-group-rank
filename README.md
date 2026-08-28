# 会員グループ管理::会員ランク管理アドオン for EC-CUBE 4.4

購入実績に応じて会員グループを自動で入れ替える EC-CUBE 4.4 用プラグインです。
[会員グループ管理プラグイン](https://github.com/kurozumi/eccube-plugin-customer-group) (CustomerGroup44) の拡張アドオンとして動作します。

「10万円買った人をゴールドに上げる」を人手でやらずに済ませます。ランクごとの
価格・閲覧できる商品・配送方法・支払方法は、会員グループ管理の各アドオンが
そのまま面倒を見ます。

![会員グループ一覧](docs/images/group-index.png)

---

## できること

| 機能 | 内容 |
|---|---|
| ランクの自動割り当て | 購入回数・購入金額の条件を満たすグループを、ログイン時に割り当てる |
| 送料無料条件 | 会員グループごとに「いくら以上」「何個以上」で送料無料にする |
| 判定の差し替え | 条件を自分で書ける。最終購入日で降格させる、といった運用にも対応できる |

**手で割り当てたグループは触りません。** 購入回数・購入金額のどちらも入っていない
グループはランク管理の対象外として、そのまま残します。

---

## 動作要件

- EC-CUBE **4.4** 系
- 会員グループ管理プラグイン (`ec-cube/customergroup44`) ^4.4 が有効化されていること
- PHP 8.2 / 8.3

---

## インストール

```
composer require ec-cube/customergrouprank44
bin/console eccube:plugin:install --code=CustomerGroupRank44
bin/console eccube:plugin:enable --code=CustomerGroupRank44
```

---

## ドキュメント

| ページ | 内容 |
|---|---|
| [使い方](docs/usage.md) | ランクの作り方、並び順、送料無料条件、他アドオンとの組み合わせ |
| [仕様](docs/specification.md) | ランクの決まり方、割り当てのタイミング、制約 |
| [動作確認](docs/acceptance.md) | 入れたあとに一周する手順（画像付き） |
| [判定の差し替え](docs/extension-samples.md) | `RankAssignerInterface` を実装して条件を変える |
| [イベント一覧](docs/event-subscribers.md) | このプラグインが購読しているイベント |

---

## 関連プラグイン

- [**会員グループ管理**](https://github.com/kurozumi/eccube-plugin-customer-group) (`ec-cube/customergroup44`) — 土台。これが無いと動きません
- [**会員グループ価格管理**](https://github.com/kurozumi/eccube-plugin-customer-group-price) (`ec-cube/customergroupprice44`) — ランクごとの価格を出します
- [**配送方法制限**](https://github.com/kurozumi/eccube-plugin-customer-group-delivery) / [**支払方法制限**](https://github.com/kurozumi/eccube-plugin-customer-group-payment) — ランクごとに選べる配送方法・支払方法を変えます

---

## サポート / ライセンス

本プラグインはEC-CUBE 1サイトにてご利用ください。
ただし、本番環境・開発環境で兼用することは問題ございません。

カスタマイズ、または他社プラグインとの競合による動作不良につきましてはサポート対象外ですが、
要望の重要度によっては、不定期の改良版をリリースしますのでまずはお問い合わせください。

本プラグインを導入したことによる不具合や被った不利益につきましては一切責任を負いません。
ご理解の程よろしくお願いいたします。
