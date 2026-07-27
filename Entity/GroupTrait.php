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
use Eccube\Attribute\EntityExtension;

#[EntityExtension(\Plugin\CustomerGroup42\Entity\Group::class)]
trait GroupTrait
{
    /**
     * @var string|null
     */
    #[ORM\Column(type: 'decimal', precision: 10, scale: 0, nullable: true, options: ['unsigned' => true])]
    private $buyTimes;

    /**
     * @var string|null
     */
    #[ORM\Column(type: 'decimal', precision: 12, scale: 2, nullable: true, options: ['unsigned' => true])]
    private $buyTotal;

    /**
     * @var string|null
     */
    #[ORM\Column(type: 'decimal', precision: 12, scale: 2, nullable: true, options: ['unsigned' => true])]
    private $deliveryFreeAmount;

    /**
     * @var string|null
     */
    #[ORM\Column(type: 'decimal', precision: 10, scale: 0, nullable: true, options: ['unsigned' => true])]
    private $deliveryFreeQuantity;

    public function getBuyTimes(): ?float
    {
        return $this->buyTimes;
    }

    public function setBuyTimes(?float $buyTimes): self
    {
        $this->buyTimes = $buyTimes;

        return $this;
    }

    public function getBuyTotal(): ?float
    {
        return $this->buyTotal;
    }

    public function setBuyTotal(?float $buyTotal): self
    {
        $this->buyTotal = $buyTotal;

        return $this;
    }

    public function getDeliveryFreeAmount(): ?float
    {
        return $this->deliveryFreeAmount;
    }

    public function setDeliveryFreeAmount(?float $deliveryFreeAmount): self
    {
        $this->deliveryFreeAmount = $deliveryFreeAmount;

        return $this;
    }

    public function getDeliveryFreeQuantity(): ?float
    {
        return $this->deliveryFreeQuantity;
    }

    public function setDeliveryFreeQuantity(?float $deliveryFreeQuantity): self
    {
        $this->deliveryFreeQuantity = $deliveryFreeQuantity;

        return $this;
    }
}
