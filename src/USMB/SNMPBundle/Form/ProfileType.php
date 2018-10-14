<?php
/**
 * Created by IntelliJ IDEA.
 * User: tfoissard
 * Date: 04/10/2018
 * Time: 10:26
 */

namespace USMB\SNMPBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * Class ProfileType
 * @package USMB\SNMPBundle\Form
 */
class ProfileType extends AbstractType
{

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('name', TextType::class)
            ->add('oid', TextType::class)
            ->add('type', ChoiceType::class, array(
                'choices' => array(
                    'String' => 'String',
                    'Integer' => 'Integer',
                    'Float' => 'Float',
                    'Time' => 'DateTime'
                )))
            ->add('save', SubmitType::class);
    }

}