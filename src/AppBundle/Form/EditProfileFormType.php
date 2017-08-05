<?php
/**
 * Created by PhpStorm.
 * User: saeed
 * Date: 8/3/17
 * Time: 1:25 AM.
 */

namespace AppBundle\Form;

use FOS\UserBundle\Form\Type\ProfileFormType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class EditProfileFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);
        $builder->add('name');
        $builder->add('last_name');
        $builder->add('age');
    }

    public function getParent()
    {
        return ProfileFormType::class;
    }

    public function getName()
    {
        return 'app_user_edit_profile';
    }
}
