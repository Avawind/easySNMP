<?php
/**
 * Created by IntelliJ IDEA.
 * User: colecler
 * Date: 04/10/2018
 * Time: 11:16
 */

namespace USMB\SNMPBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * Class DeviceType
 * @package USMB\SNMPBundle\Form
 */
class DeviceType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('name', TextType::class)
            ->add('host', TextType::class)
            ->add('location', TextType::class)
            ->add('community', TextType::class)
            ->add('profiles', EntityType::class, array(
                'class' => 'USMB\SNMPBundle\Entity\Profile',
                'choice_label' => 'name',
                'multiple' => true
            ))
            ->add('version', ChoiceType::class, array(
                'choices' => array(
                    'v2' => 'V2',
                    'v3' => 'V3'
                ),
            ))
            ->add('user', TextType::class)
            ->add('password', TextType::class)
            ->add('cryptoKey', TextType::class)
            ->add('save', SubmitType::class);
    }
}