<?php

namespace App\EventSubscriber;

use App\Entity\Question;
use App\Entity\User;
use EasyCorp\Bundle\EasyAdminBundle\Event\BeforeEntityUpdatedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Security\Core\Security;

class BlamebleSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly Security $security
    ) {
    }

    public function onBeforeEntityUpdatedEvent(BeforeEntityUpdatedEvent $event): void
    {
        $question = $event->getEntityInstance();
        if (!$question instanceof Question) {
            return;
        }
        $user = $this->security->getUser();
        if (!$user instanceof User) {
            throw new \LogicException('Currently logged in user is not as instance of User?!');
        }

        $question->setUpdatedBy($user);
    }

    public static function getSubscribedEvents(): array
    {
        return [
//            BeforeEntityUpdatedEvent::class => 'onBeforeEntityUpdatedEvent',
        ];
    }
}
