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
use Eccube\Annotation\EntityExtension;

/**
 * @EntityExtension("Plugin\CustomerGroup42\Entity\Group")
 */
trait GroupTrait
{
    /**
     * @var string|null
     *
     * @ORM\Column(type="decimal", precision=10, scale=0, nullable=true, options={"unsigned":true})
     */
    private $buyTimes;

    /**
     * @var string|null
     *
     * @ORM\Column(type="decimal", precision=12, scale=2, nullable=true, options={"unsigned":true})
     */
    private $buyTotal;

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
}
