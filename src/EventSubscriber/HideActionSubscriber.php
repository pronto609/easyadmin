<?php

namespace App\EventSubscriber;

use App\Entity\Question;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Dto\ActionDto;
use EasyCorp\Bundle\EasyAdminBundle\Event\BeforeCrudActionEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class HideActionSubscriber implements EventSubscriberInterface
{
    public function onBeforeCrudActionEvent(BeforeCrudActionEvent $event): void
    {
        if (!$adminContext = $event->getAdminContext()) {
            return;
        }

        if (!$crudDto = $adminContext->getCrud()) {
            return;
        }

        if ($crudDto->getEntityFqcn() !== Question::class) {
            return;
        }

        // disable action entirely for delete, detail & edit pages
        $question = $adminContext->getEntity()->getInstance();
        if ($question instanceof Question && $question->getIsApproved()) {
            $crudDto->getActionsConfig()->disableActions(Action::DELETE);
        }

        // returns the array of actual actions that will be enabled
        // for the current page
        $action = $crudDto->getActionsConfig()->getActions();

        if (!$deleteAction = $action[Action::DELETE] ?? null) {
            return;
        }

        /** @var $deleteAction ActionDto */
        $deleteAction->setDisplayCallable(function (Question $question) {
            return !$question->getIsApproved();
        });
    }

    public static function getSubscribedEvents(): array
    {
        return [
            BeforeCrudActionEvent::class => 'onBeforeCrudActionEvent',
        ];
    }
}
