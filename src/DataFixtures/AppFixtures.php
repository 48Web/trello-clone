<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\Board;
use App\Entity\BoardList;
use App\Entity\Card;
use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class AppFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        // Create a default user
        $user = new User();
        $user->setName('Demo User');
        $user->setEmail('demo@example.com');
        $manager->persist($user);

        // Create a sample board
        $board = new Board();
        $board->setTitle('Welcome Board');
        $board->setDescription('This is your first board');
        $board->setUser($user);
        $board->setPosition(1);
        $manager->persist($board);

        // Create lists for the board
        $todoList = new BoardList();
        $todoList->setTitle('To Do');
        $todoList->setBoard($board);
        $todoList->setPosition(1);
        $manager->persist($todoList);

        $doingList = new BoardList();
        $doingList->setTitle('In Progress');
        $doingList->setBoard($board);
        $doingList->setPosition(2);
        $manager->persist($doingList);

        $doneList = new BoardList();
        $doneList->setTitle('Done');
        $doneList->setBoard($board);
        $doneList->setPosition(3);
        $manager->persist($doneList);

        // Create sample cards
        $card1 = new Card();
        $card1->setTitle('Welcome to Trello Clone');
        $card1->setDescription('This is a sample card to get you started');
        $card1->setList($todoList);
        $card1->setPosition(1);
        $manager->persist($card1);

        $card2 = new Card();
        $card2->setTitle('Create your first board');
        $card2->setDescription('Boards help you organize your projects');
        $card2->setList($doingList);
        $card2->setPosition(1);
        $manager->persist($card2);

        $card3 = new Card();
        $card3->setTitle('Add team members');
        $card3->setDescription('Collaborate with your team on boards');
        $card3->setList($doneList);
        $card3->setPosition(1);
        $manager->persist($card3);

        $manager->flush();
    }
}
