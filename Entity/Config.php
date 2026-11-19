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

namespace Plugin\CustomerGroupRank42\Entity;

use Doctrine\ORM\Mapping as ORM;
use Eccube\Entity\AbstractEntity;

if (!class_exists(Config::class)) {
    /**
     * 会員ランクの設定。
     *
     * **行が無いときは「購入回数または購入金額（OR）」として扱う。** 以前の版は
     * OR だけだったので、`git pull` でこの版へ上げた店（PluginManager::update() が
     * 走らない）も、行が無いまま今までどおり OR で判定される。新しくインストール
     * した店には install() が AND の行を入れる。
     *
     * @ORM\Table(name="plg_customer_group_rank_config")
     *
     * @ORM\Entity(repositoryClass="Plugin\CustomerGroupRank42\Repository\ConfigRepository")
     */
    class Config extends AbstractEntity
    {
        /**
         * 購入回数と購入金額の「どちらか」を満たせばランクを付ける（以前の版の挙動）。
         */
        public const CONDITION_OR = 'or';

        /**
         * 購入回数と購入金額の「両方」を満たしたときだけランクを付ける。
         */
        public const CONDITION_AND = 'and';

        /**
         * @var int|null
         *
         * @ORM\Column(type="integer", options={"unsigned":true})
         *
         * @ORM\Id()
         *
         * @ORM\GeneratedValue(strategy="IDENTITY")
         */
        private $id;

        /**
         * @var string
         *
         * @ORM\Column(name="rank_condition", type="string", length=8, options={"default":"or"})
         */
        private $rankCondition = self::CONDITION_OR;

        public function getId(): ?int
        {
            return $this->id;
        }

        public function getRankCondition(): string
        {
            return self::CONDITION_AND === $this->rankCondition ? self::CONDITION_AND : self::CONDITION_OR;
        }

        public function setRankCondition(string $rankCondition): self
        {
            $this->rankCondition = self::CONDITION_AND === $rankCondition ? self::CONDITION_AND : self::CONDITION_OR;

            return $this;
        }

        public function isMatchAll(): bool
        {
            return self::CONDITION_AND === $this->getRankCondition();
        }
    }
}
