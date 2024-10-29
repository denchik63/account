<?php

namespace App\Client\AccountOperations;

use App\Client\AccountOperations\Db\AccountCreationInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(name: 'app:create-account')]
class CreateAccountCommand extends Command
{
    public function __construct(private readonly AccountCreationInterface $accountCreation)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setDefinition([new InputOption('initialBalance', 'i', InputOption::VALUE_OPTIONAL)]);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $initialBalance = (float) ($input->getOption('initialBalance') ?? 0.0);

        $output->writeln( sprintf('Creating new account with initial balance "%f"...', $initialBalance));

        $id = $this->accountCreation->create($initialBalance);

        $output->writeln( sprintf('Created new account with id "%s"', $id));

        return Command::SUCCESS;
    }
}