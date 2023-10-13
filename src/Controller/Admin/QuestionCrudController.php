<?php

namespace App\Controller\Admin;

use App\EasyAdmin\VotesField;
use App\Entity\Question;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\Field;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\Security\Core\Security;

#[IsGranted('ROLE_MODERATOR')]
class QuestionCrudController extends AbstractCrudController
{
    public function __construct(
        private readonly Security $security
    ) {
    }

    public static function getEntityFqcn(): string
    {
        return Question::class;
    }


    public function configureFields(string $pageName): iterable
    {
        yield IdField::new('id')
        ->onlyOnIndex();
        yield Field::new('slug')
            ->hideOnIndex()
            ->setFormTypeOption(
            'disabled',
            $pageName !== Crud::PAGE_NEW
            )
        ;
        yield Field::new('name')
        ->setSortable(false);
        yield AssociationField::new('topic');
        yield TextareaField::new('question')
            ->hideOnIndex()
        ->setFormTypeOptions([
            'row_attr' => [
                'data-controller' => 'snarkdown'
            ],
            'attr' => [
                'data-snarkdown-target' => 'input',
                'data-action' => 'snarkdown#render'
            ]
        ])
        ->setHelp('Preview:');
        yield VotesField::new('votes', 'Total Votes')
            ->setTextAlign('right')
            ->setPermission('ROLE_SUPER_ADMIN');
        yield AssociationField::new('askedBy')
        ->autocomplete()
        ->formatValue(static function ($value, ?Question $question) {
            if (!$user = $question?->getAskedBy()) {
                return null;
            }

            return sprintf('%s&nbsp;(%s)', $user->getEmail(), $user->getQuestions()->count());
        })
        ->setQueryBuilder(function (QueryBuilder $queryBuilder) {
            $queryBuilder->andWhere('entity.enabled = :enabled')
                ->setParameter('enabled', true);
        });
        yield AssociationField::new('answers')
            ->setFormTypeOption('label', 'id')
            ->setFormTypeOption('by_reference', false)
        ->autocomplete();
        yield Field::new('createdAt')
        ->hideOnForm();
        yield AssociationField::new('updatedBy')
        ->onlyOnDetail();

    }

    public function configureCrud(Crud $crud): Crud
    {
        return parent::configureCrud($crud)
            ->setDefaultSort([
                'askedBy.enabled' => 'DESC',
                'createdAt' => 'DESC'
            ]);
    }

    public function configureActions(Actions $actions): Actions
    {
        $viewAction = Action::new('view')
        ->linkToUrl(function (Question $question) {
            return $this->generateUrl('app_question_show', ['slug' => $question->getSlug()]);
        })
        ->addCssClass('btn btn-success')
        ->setIcon('fa fa-eye')
        ->setLabel('View on site');

        $approveAction = Action::new('approve')
            ->displayAsButton()
        ->addCssClass('btn btn-success')
        ->setIcon('fa fa-check-circle')
        ->linkToCrudAction('approve')
        ->setTemplatePath('admin/approve_action.html.twig')
            ->displayIf(static function (Question $question): bool {
                return !$question->getIsApproved();
            })
        ;
        return parent::configureActions($actions)
            ->update(Crud::PAGE_INDEX, Action::DELETE, function (Action $action) {
                $action->displayIf(static function (Question $question) {
//                    return !$question->getIsApproved();
                    return true;
                });
                return $action;
            })
            ->setPermission(Action::INDEX, 'ROLE_MODERATOR')
            ->setPermission(Action::DETAIL, 'ROLE_MODERATOR')
            ->setPermission(Action::EDIT, 'ROLE_MODERATOR')
            ->setPermission(Action::NEW, 'ROLE_SUPER_ADMIN')
            ->setPermission(Action::DELETE, 'ROLE_SUPER_ADMIN')
            ->setPermission(Action::BATCH_DELETE, 'ROLE_SUPER_ADMIN')
            ->add(Crud::PAGE_DETAIL, $viewAction)
            ->add(Crud::PAGE_INDEX, $viewAction)
            ->add(Crud::PAGE_DETAIL, $approveAction)
            ;
    }

    public function configureFilters(Filters $filters): Filters
    {
        return parent::configureFilters($filters)
            ->add('topic')
            ->add('createdAt')
            ->add('votes')
            ->add('name');
    }

    public function updateEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        $user = $this->security->getUser();
        if (!$user instanceof User) {
            throw new \LogicException('Currently logged in user is not as instance of User?!');
        }

        $entityInstance->setUpdatedBy($user);
        parent::updateEntity($entityManager, $entityInstance);
    }

    /**
     * @param Question $entityInstance
     */
    public function deleteEntity(EntityManagerInterface $entityManager, $entityInstance): void
    {
        if ($entityInstance->getIsApproved()) {
            throw new \Exception('Deliting approved questions is forbidden');
        }

        parent::deleteEntity($entityManager, $entityInstance);
    }

    public function approve(AdminContext $adminContext, EntityManagerInterface $entityManager, AdminUrlGenerator $adminUrlGenerator)
    {
        $question = $adminContext->getEntity()->getInstance();

        if (!$question instanceof Question) {
            throw new \LogicException('Entity is missing or not Question?!');
        }

        $question->setIsApproved(true);
        $entityManager->flush();

        $targetUrl = $adminUrlGenerator
            ->setController(self::class)
            ->setAction(Crud::PAGE_DETAIL)
            ->setEntityId($question->getId())
            ->generateUrl()
        ;
        return $this->redirect($targetUrl);
    }
}
