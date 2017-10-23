<?php

namespace AppBundle\Form;


use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class UserType extends AbstractType {

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $i18n = $options['i18n']['es'];
        $builder
            ->add('email', EmailType::class, array(
                'label' => $i18n['forms']['profile_form']['email'] . ':',
                'required' => true
            ))
            ->add('companyName', TextType::class, array(
                'label' => $i18n['forms']['profile_form']['company_name'] . ':',
                'required' => false
            ))
            ->add('cif', TextType::class, array(
                'label' => $i18n['forms']['profile_form']['cif'] . ':',
                'required' => false
            ))
            ->add('address', TextType::class, array(
                'label' => $i18n['forms']['profile_form']['address'] . ':',
                'required' => false
            ))
            ->add('contactPerson', TextType::class, array(
                'label' => $i18n['forms']['profile_form']['contact_person'] . ':',
                'required' => false
            ))
            ->add('sector', ChoiceType::class, array(
                'label' => $i18n['forms']['profile_form']['sector'] . ':',
                'required' => false,
                'choices' => array(
                    'Agricultura, silvicultura, ganadería y pesca' => 'AGRICULTURA, SILVICULTURA, GANADERÍA Y PESCA',
                    'Construcción' => 'CONSTRUCCIÓN',
                    'Detallistas' => 'DETALLISTAS',
                    'Fabricantes' => 'FABRICANTES',
                    'Finanzas, seguros y bienes raíces' => 'FINANZAS, SEGUROS Y BIENES RAÍCES',
                    'Mayoristas' => 'MAYORISTAS',
                    'Minería' => 'MINERÍA',
                    'Organismos oficiales' => 'ORGANISMOS OFICIALES',
                    'Servicios' => 'SERVICIOS',
                    'Transportes, comunicaciones y servicios públicos' => 'TRANSPORTES, COMUNICACIONES Y SERVICIOS PÚBLICOS',
                    'Otros' => 'OTROS'
                )
            ))
            ->add('numberEmployees', ChoiceType::class, array(
                'label' => $i18n['forms']['profile_form']['num_employees'] . ':',
                'required' => false,
                'choices' => array(
                    'Hasta 10 empleados' => '1-10',
                    'Hasta 30 empleados' => '11-30',
                    'Hasta 50 empleados' => '31-50',
                    'Hasta 100 empleados' => '51-100',
                    'Más de 100 empleados' => '>100'
                )
            ))
            ->add('logo', FileType::class, array(
                'label' => $i18n['forms']['profile_form']['logo'] . ':',
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
            'data_class' => 'AppBundle\Entity\User'
        ));
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return 'user_form';
    }

}
