<?php

namespace AppBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class UserType extends AbstractType {

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('email', EmailType::class, array(
                'label' => 'Email:',
                'required' => true
            ))
            ->add('companyName', TextType::class, array(
                'label' => 'Nombre de la compañía:',
                'required' => false
            ))
            ->add('cif', TextType::class, array(
                'label' => 'CIF:',
                'required' => false
            ))
            ->add('address', TextType::class, array(
                'label' => 'Domicilio social:',
                'required' => false
            ))
            ->add('contactPerson', TextType::class, array(
                'label' => 'Persona de contacto:',
                'required' => false
            ))
            ->add('sector', ChoiceType::class, array(
                'label' => 'Sector:',
                'required' => false,
                'choices' => array(
                    'Hostelería, comida y bebida' => 'Hostelería, comida y bebida',
                    'Salud, belleza y bienestar' => 'Salud, belleza y bienestar',
                    'Venta al por menor' => 'Venta al por menor',
                    'Servicios' => 'Servicios',
                    'Ocio y entretenimiento' => 'Ocio y entretenimiento',
                    'Otro' => 'Otro'
                )
            ));
    }
    
    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
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
