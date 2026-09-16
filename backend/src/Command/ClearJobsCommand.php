<?php

namespace App\Command;

use App\Entity\Job;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

#[AsCommand(
    name: 'app:jobs:clear',
    description: 'Delete all jobs from the database.'
)]
class ClearJobsCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager
    ) {
        parent::__construct();
    }

    protected function execute(
        InputInterface $input,
        OutputInterface $output
    ): int {
        $repository = $this->entityManager
            ->getRepository(Job::class);

        $jobs = $repository->findAll();

        $count = count($jobs);

        if ($count === 0) {
            $output->writeln('');
            $output->writeln(
                '<info>No jobs found. The table is already empty.</info>'
            );
            $output->writeln('');

            return Command::SUCCESS;
        }

        $output->writeln('');
        $output->writeln(
            sprintf(
                '<comment>%d jobs currently exist in the database.</comment>',
                $count
            )
        );

        $output->writeln(
            '<comment>This will permanently delete all of them.</comment>'
        );

        $output->writeln('');

        $question = new ConfirmationQuestion(
            'Continue? [y/N] ',
            false
        );

        $helper = new QuestionHelper();

        if (!$helper->ask($input, $output, $question)) {
            $output->writeln('');
            $output->writeln(
                '<info>Operation cancelled. No jobs were deleted.</info>'
            );
            $output->writeln('');

            return Command::SUCCESS;
        }

        foreach ($jobs as $job) {
            $this->entityManager->remove($job);
        }

        $this->entityManager->flush();

        $output->writeln('');
        $output->writeln(
            sprintf(
                '<info>Successfully deleted %d jobs.</info>',
                $count
            )
        );
        $output->writeln('');

        return Command::SUCCESS;
    }
}