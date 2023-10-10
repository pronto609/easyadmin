<?php

namespace App\DataFixtures;

use App\Factory\AnswerFactory;
use App\Factory\QuestionFactory;
use App\Factory\TopicFactory;
use App\Factory\UserFactory;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager)
    {
        // Load Users
        UserFactory::new()
            ->withAttributes([
                'email' => 'celo@example.com',
                'plainPassword' => 'tada',
            ])
            ->promoteRole('ROLE_SUPER_ADMIN')
            ->create();

        UserFactory::new()
            ->withAttributes([
                'email' => 'admin@example.com',
                'plainPassword' => 'tada',
            ])
            ->promoteRole('ROLE_ADMIN')
            ->create();

        UserFactory::new()
            ->withAttributes([
                'email' => 'moderatoradmin@example.com',
                'plainPassword' => 'tada',
            ])
            ->promoteRole('ROLE_MODERATOR')
            ->create();

        UserFactory::new()
            ->withAttributes([
                'email' => 'tisha@symfonycasts.com',
                'plainPassword' => 'tishapass',
                'firstName' => 'Tisha',
                'lastName' => 'The Cat',
                'avatar' => 'tisha.png',
            ])
            ->create();

        // Load Topics
        TopicFactory::new()->createMany(5);

        // Load Questions
        QuestionFactory::new()->createMany(20);

        QuestionFactory::new()
            ->unpublished()
            ->createMany(5);

        // Load Answers
        AnswerFactory::new()->createMany(100);

        $manager->flush();
    }
}
