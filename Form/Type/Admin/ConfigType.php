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

namespace Plugin\CustomerGroupRank42\Form\Type\Admin;

use Plugin\CustomerGroupRank42\Entity\Config;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Choice;
use Symfony\Component\Validator\Constraints\NotBlank;

class ConfigType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->add('rankCondition', ChoiceType::class, [
            'label' => 'customer_group_rank.admin.config.rank_condition',
            'expanded' => true,
            'multiple' => false,
            'choices' => [
                'customer_group_rank.admin.config.rank_condition.and' => Config::CONDITION_AND,
                'customer_group_rank.admin.config.rank_condition.or' => Config::CONDITION_OR,
            ],
            'constraints' => [
                new NotBlank(),
                new Choice(['choices' => [Config::CONDITION_AND, Config::CONDITION_OR]]),
            ],
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Config::class,
        ]);
    }
}
