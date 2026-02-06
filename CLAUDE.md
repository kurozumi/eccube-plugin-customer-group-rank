# CustomerGroupRank42

会員グループ管理プラグインのアドオン。購入実績に基づく会員ランク自動登録と、グループごとの送料無料条件を提供。

## 依存プラグイン
- CustomerGroup42（会員グループ管理プラグイン）

## 実装機能

### 会員ランク自動登録
- 購入回数・購入金額に基づいて会員グループを自動登録
- ログイン時に条件判定を実行
- 複数条件マッチ時は優先度最上位のグループを適用
- RankInterfaceによるカスタマイズ可能

### 会員グループごとの送料無料条件
- グループ単位で送料無料の金額条件を設定可能
- グループ単位で送料無料の数量条件を設定可能
- PurchaseFlowで送料計算後に適用

## 主要クラス

| クラス | 役割 |
|--------|------|
| `Service\Rank\Context` | ランク判定の実行コンテキスト |
| `Service\Rank\Rank` | デフォルトのランク判定ロジック |
| `Service\Rank\RankInterface` | ランク判定のカスタマイズ用インターフェース |
| `Service\PurchaseFlow\Processor\GroupDeliveryFreePreprocessor` | グループ別送料無料処理 |
| `Security\EventListener\LoginListener` | ログイン時のランク判定トリガー |
| `Entity\GroupTrait` | Groupエンティティの拡張（buyTimes, buyTotal, deliveryFreeAmount, deliveryFreeQuantity） |

## 対応バージョン
- EC-CUBE 4.2 / 4.3
- PHP 7.4+
