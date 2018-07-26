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
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class CompanyNameType extends AbstractType {

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $i18n = $options['i18n'];
        $builder
            ->add('companyName', TextType::class, array(
                'label' => $i18n['forms']['company_name_form']['company_name'] . ':',
                'required' => true
            ))
            ->add('logo', FileType::class, array(
                'label' => $i18n['forms']['company_name_form']['logo'] . ':',
                'required' => false
            ));
        $builder->get('logo')
            ->addModelTransformer(new CallBackTransformer(
                function($imageUrl) {
                    return null;
                },
                function($imageUrl) {
                    return $imageUrl;
                }
            ));
    }
    
    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setRequired('i18n');
        $resolver->setDefaults(array(
            'data_class' => 'AppBundle\Entity\Company'
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'company_name_form';
    }

}
