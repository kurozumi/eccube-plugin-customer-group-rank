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

namespace Plugin\CustomerGroupRank42\Form\Extension\Admin;

use Eccube\Form\Type\PriceType;
use Plugin\CustomerGroup42\Form\Type\Admin\GroupType;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\Range;
use Symfony\Component\Validator\Constraints\Regex;

class GroupTypeExtension extends AbstractTypeExtension
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('buyTimes', NumberType::class, [
                'label' => '購入回数',
                'constraints' => [
                    new Regex([
                        'pattern' => "/^\d+$/u",
                        'message' => 'form_error.numeric_only',
                    ]),
                    new Range([
                        'min' => 1,
                    ]),
                ],
            ])
            ->add('buyTotal', PriceType::class, [
                'label' => '購入金額',
                'required' => false,
                'constraints' => [
                    new Range([
                        'min' => 1,
                    ]),
                ],
            ]);
    }

    public static function getExtendedTypes(): iterable
    {
        yield GroupType::class;
    }
}
