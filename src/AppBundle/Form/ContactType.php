<?php

/*!
 * ammana.es - job protocols generator
 * https://github.com/NoLegalTech/ammana
 * Copyright (C) 2018 Zeres Abogados y Consultores Laborales SLP <zeres@zeres.es>
 * https://github.com/NoLegalTech/ammana/blob/master/LICENSE
 */

namespace AppBundle\Form;


use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;

class ContactType extends AbstractType {

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $i18n = $options['i18n'];
        $builder
            ->add('name', TextType::class, array(
                'label' => $i18n['forms']['contact_form']['name'] . ':',
                'required' => true,
                'attr' => [ 'placeholder' => 'Nombre' ]
            ))
            ->add('company', TextType::class, array(
                'label' => $i18n['forms']['contact_form']['company'] . ':',
                'required' => true,
                'attr' => [ 'placeholder' => 'Empresa' ]
            ))
            ->add('email', TextType::class, array(
                'label' => $i18n['forms']['contact_form']['email'] . ':',
                'required' => true,
                'attr' => [ 'placeholder' => 'e-mail' ]
            ))
            ->add('comment', TextareaType::class, array(
                'label' => $i18n['forms']['contact_form']['comment'] . ':',
                'required' => true,
                'attr' => [ 'placeholder' => 'Comentario' ]
            ));
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired('i18n');
        $resolver->setDefaults(array(
            'data_class' => 'AppBundle\Entity\ContactMessage'
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'contact_form';
    }

}
