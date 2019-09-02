<?php

/*!
 * ammana.es - job protocols generator
 * https://github.com/NoLegalTech/ammana
 * Copyright (C) 2018 Zeres Abogados y Consultores Laborales SLP <zeres@zeres.es>
 * https://github.com/NoLegalTech/ammana/blob/master/LICENSE
 */

namespace AppBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class AdviserRegisterType extends AbstractType {

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $i18n = $options['i18n'];
        $builder
            ->add('email', EmailType::class, array(
                'label' => $i18n['forms']['profile_form']['email'] . ':',
                'required' => true,
                'attr' => [ 'placeholder' => 'Correo electrónico' ]
            ))
            ->add('password', PasswordType::class, array(
                'label' => $i18n['forms']['profile_form']['password'] . ':',
                'required' => true,
                'attr' => [ 'placeholder' => 'Contraseña' ]
            ))
            ->add('cif', TextType::class, array(
                'label' => $i18n['forms']['profile_form']['cif'] . ':',
                'required' => false
            ))
            ->add('address', TextType::class, array(
                'label' => $i18n['forms']['profile_form']['address'] . ':',
                'required' => false
            ))
            ->add('pack', ChoiceType::class, array(
                'label' => 'Escoge un pack:',
                'label' => $i18n['forms']['adviser_register_form']['pack'] . ':',
                'required' => true,
                'choices' => array(
                    'Pack S (5 protocolos x 70€ cada uno)' => 'S',
                    'Pack M (15 protocolos x 65€ cada uno)' => 'M',
                    'Pack L (25 protocolos x 60€ cada uno)' => 'L'
                )
            ));
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired('i18n');
        $resolver->setDefaults(array(
            'data_class' => 'AppBundle\Entity\AdviserRegister'
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'adviser_register_form';
    }

}

