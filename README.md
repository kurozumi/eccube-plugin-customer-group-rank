# 会員グループ管理::会員ランク管理アドオン

本プラグインは[会員グループ管理プラグイン](https://www.ec-cube.net/products/detail.php?product_id=2439) をインストール・有効化する必要があります。

会員グループ管理::会員ランク管理アドオンは、購入金額や購入回数に応じて会員グループを自動登録することができるプラグインです。

ゴールド会員、シルバー会員、ブロンズ会員などの会員グループを登録してそれぞれに購入金額と購入回数を登録しておくと、会員がログインしたときに条件にマッチした会員グループが会員に登録されます。
条件にマッチした会員グループが複数あった場合、優先度が最上位の会員グループが登録されます。

会員グループの優先度は会員グループ一覧ページで設定できます。
会員グループをドラッグアンドドロップで並べ替えるだけで設定できます。

# 機能一覧

## 会員ランク自動登録機能
購入金額・購入回数に応じて会員グループを自動的に登録します。

## 会員グループごとの送料無料条件
会員グループごとに送料無料条件を設定できます。

- **送料無料条件（税込み金額）**: 設定した金額以上の購入で送料無料
- **送料無料条件（数量）**: 設定した数量以上の購入で送料無料

例えば、ゴールド会員は5,000円以上で送料無料、シルバー会員は10,000円以上で送料無料といった設定が可能です。

# 設定方法

## 会員ランクの設定
1. 会員グループを作成
2. 会員グループに購入回数と購入金額を設定

## 送料無料条件の設定
1. 会員グループ編集画面を開く
2. 「送料無料条件（税込み金額）」または「送料無料条件（数量）」を設定

# 会員グループが会員に登録されるタイミング
+ 会員ログイン時に条件にマッチした会員グループが会員に登録されます。

# ランクの条件（AND / OR）

会員グループに入れた「購入回数」と「購入金額」の組み合わせ方を、プラグイン一覧の「設定」（会員ランク設定）で選べます。会員グループ編集画面の購入金額の下にも、いまの条件と設定への入口が出ます。

| 設定 | ランクが付く会員 |
|---|---|
| 両方を満たしたとき（AND） | 購入回数と購入金額の両方が条件以上。空欄の条件は使わない（片方だけ入れたグループは、その条件だけで判定） |
| どちらかを満たしたとき（OR） | 購入回数か購入金額のどちらかが条件以上（4.3.1 までの挙動） |

- **新しくインストールした店の既定は AND**、**以前の版から更新した店は OR のまま**です（オーナーズストアからの更新でも、`git pull` での更新でも）。
- どちらの設定でも、購入回数と購入金額の両方が空欄のグループはランクの対象になりません（手動で割り当てるグループとして扱い、ログインで外しません）。
- 設定を変えると、**会員が次にログインしたときから**新しい条件で判定します。OR から AND に切り替えると、片方しか満たしていない会員は次のログインでそのランクから外れます。ランクに価格・送料無料・支払方法を紐づけている場合は、それらも変わります。

# 4.3.2 で変わること

- **ランクの条件（AND / OR）を選べるようになりました。** 既存の店は OR のままで、判定の結果は変わりません。新しくインストールした店は AND になります。
- **ログインしたときのランクの判定を、本体の処理より先に走らせるようにしました**（`security.interactive_login` の priority 10）。保存されていたカートを取り込むときに、前のランクの価格で検証されることがなくなります。
- 設定はテーブル `plg_customer_group_rank_config` に持ちます。

## 店がすること

- **オーナーズストアから更新する店**: 何もしなくて構いません（OR のまま）。AND にしたいときは「設定」で切り替えてください。
- **`git pull` などでファイルを差し替えて更新する店**: 設定のテーブルを作るため、`bin/console eccube:plugin:schema-update CustomerGroupRank42` を実行してからキャッシュを削除してください。実行するまでも判定は OR のまま動きますが、設定画面では保存できません。
- 独自のランク判定（`RankInterface` の実装）で `GroupRepository::getQueryBuilderBySearchData(['buyTimes' => …, 'buyTotal' => …])` を呼んでいる場合、その結果もこの設定に従います（OR のままなら変わりません）。

# ランク昇格条件のカスタマイズ方法

本プラグインはランク昇格（※会員グループの自動登録）条件を変更することができます。
デフォルトの条件は購入金額と購入回数ですが、例えば会員の最終購入日から1ヶ月過ぎていたら会員グループから除外するといったことも可能です。

Customizeディレクトリで以下の実装を行う必要があります。
+ RankInterfaceを実装したランク決定クラス。
+ services.yamlにRankInterfaceを実装したクラスを設定。priorityを99以下にすると反映されます。


### ランク決定クラスの実装例

以下は本プラグインの実装です。

```php
namespace Plugin\CustomerGroupRank42\Service\Rank;


use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Eccube\Entity\Customer;
use Plugin\CustomerGroup42\Entity\Group;

class Rank implements RankInterface
{
    protected EntityManagerInterface $entityManager;

    public function __construct(EntityManagerInterface $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * 優先度が最上位のグループを会員に設定する
     */
    public function apply(Customer $customer): void
    {
        // 会員グループをクリアする
        $customer->getGroups()->clear();

        // 対象の会員ブループが見つかったら登録
        $groups = $this->getGroups($customer);
        if ($groups->count() > 0) {
            /** @var Group $group */
            $group = $groups->first();
            $customer->addGroup($group);
        }
    }

    /**
     * 会員に適用可能なグループ一覧を取得
     */
    protected function getGroups(Customer $customer): ArrayCollection
    {
        $searchData = [
            'buyTimes' => $customer->getBuyTimes(),
            'buyTotal' => $customer->getBuyTotal()
        ];
        $groups = $this->entityManager->getRepository(Group::class)->getQueryBuilderBySearchData($searchData)
            ->getQuery()
            ->getResult();

        return new ArrayCollection($groups);
    }
}
```

### services.yamlの設定例

以下は本プラグインの設定です。

```yaml
services:
  Plugin\CustomerGroupRank42\Service\Rank\Rank:
    tags:
      - { name: 'plugin.customer.group.rank', priority: 100 }
    arguments:
      - '@doctrine.orm.default_entity_manager'
```

# 対応バージョン

- EC-CUBE 4.2系
- EC-CUBE 4.3系
- PHP 7.4 〜 8.1（EC-CUBE 4.2）/ 8.1 〜 8.3（EC-CUBE 4.3）
- MySQL 5.7 / 8.0、PostgreSQL 10 〜 14（EC-CUBE 4.2）/ MySQL 8.0 〜 8.4、PostgreSQL 12 〜 16（EC-CUBE 4.3）

# 注意事項

カスタマイズ、または他社プラグインとの競合による動作不良つきましてはサポート対象外です。

本プラグインを導入したことによる不具合や被った不利益につきましては一切責任を負いません。
ご理解の程よろしくお願いいたします。
