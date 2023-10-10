<?php

namespace App\Controller\Admin;

use App\Entity\Answer;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\Field;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;

class AnswerCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Answer::class;
    }


    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')
        ->onlyOnIndex();
        yield Field::new('answer');
//        ->setMaxLength(50);
        yield IntegerField::new('votes')
        ->setTemplatePath('admin/field/votes.html.twig');
        yield AssociationField::new('question')
        ->hideOnIndex()
        ->autocomplete();
        yield AssociationField::new('answeredBy')
        ->autocomplete();
        yield Field::new('createdAt')
            ->hideOnForm();
        yield Field::new('updatedAt')
        ->onlyOnDetail();
    }

}
