# 拡張のサンプル

ランクの当て方を足す例です。**そのままコピーして使えます。**

置き場所は `app/Customize/` でも、自分のプラグインの中でも構いません。
`RankInterface` を実装したクラスを置けば `#[AutoconfigureTag]` が自動でタグを
付けます。**services.yaml は要りません。**

## 決まり

**priority の降順に、すべて呼ばれます。** 最初の1つで打ち切りません。
既定の `Rank`（priority 100）が先に走り、その後に足したものが走ります。

つまり**後から走ったものが前の結果を上書きできます。** 既定を残したまま、
特定の条件だけ差し替える書き方ができます。

priority を付けたいときはクラスに書きます。

```php
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;

// 既定(100)より後に走るので、既定が当てたグループを上書きできる
#[AsTaggedItem(priority: 99)]
class MyRank implements RankInterface
```

なお `#[AsTaggedItem]` の第1引数は `index` で、**タグ名ではありません。**
priority だけを名前付き引数で渡してください。

## 呼ばれる場所

**ログインしたときだけ**走ります（`LoginListener`）。購入した直後にランクを
上げたい場合は、別のきっかけを自分で用意する必要があります。

**ランクの判定はログインでいちばん先に走ります**（priority 10）。会員グループは
価格・販売可否・配送・支払方法を左右するので、ランクを当ててから他が読む順に
してあります。

## 例: 特定の会員グループを固定する

契約で等級が決まっている会員は、購入実績で動かしたくない、という例です。

```php
<?php

namespace Customize\CustomerGroupRank;

use Doctrine\ORM\EntityManagerInterface;
use Eccube\Entity\Customer;
use Plugin\CustomerGroup44\Entity\Group;
use Plugin\CustomerGroupRank44\Service\Rank\RankInterface;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;

// 既定(100)の後に走らせて、既定が当てたランクを打ち消す
#[AsTaggedItem(priority: 99)]
class ContractedRank implements RankInterface
{
    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    public function apply(Customer $customer): void
    {
        // 「契約等級」を控えている前提（会員のメモ欄など）
        $fixed = $customer->getNote();
        if (null === $fixed || '' === $fixed) {
            return;
        }

        $Group = $this->entityManager->getRepository(Group::class)
            ->findOneBy(['name' => $fixed]);

        if (null === $Group || $customer->getGroups()->contains($Group)) {
            return;
        }

        $customer->addGroup($Group);
        $Group->addCustomer($customer);
    }
}
```

## 気をつけること

### ランクが管理していないグループを外さない

**これがこのプラグインで最も事故が起きやすいところです。**

会員グループは価格・閲覧可否・配送・支払方法を左右します。所属グループを
まとめて消すと、会員登録アドオンや管理画面で割り当てたグループが
**ログインのたびに失われ、二度と戻りません。**

既定の `Rank` は、購入実績の条件（`buyTimes` / `buyTotal`）を持つグループだけを
外してから当て直します。自分で書くときも同じ配慮をしてください。

### `flush()` を呼ばない

保存は呼び出し側（`LoginListener`）が1回だけ行います。ここで流すと、
複数の実装が走ったときに中途半端な状態が書き込まれます。

### 重いことをしない

**ログインのたびに走ります。** 会員数の多い店では、ここでの1クエリが
そのままログインの待ち時間になります。

## 効いているか確かめる

```
php bin/console debug:container --tag=plugin.customer.group.rank
```

priority の降順に並びます。**出てこないときはタグが付いていません。**
`RankInterface` を実装しているか確かめてください。
