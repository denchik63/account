<?php

namespace App\Server;

use App\Server\Event\MessageEvent;
use App\Server\Messaging\Exception\ExceptionInterface as MessagingException;
use App\Server\Messaging\MessagingInterface;
use App\Server\Messaging\Model\BaseMessageInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

#[AsCommand(name: 'app:queue-worker')]
class WorkerCommand extends Command
{
    public function __construct(
        private readonly MessagingInterface $messagingHandler,
        private readonly EventDispatcherInterface $eventDispatcher,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setDefinition([new InputArgument('queueName', InputArgument::REQUIRED)]);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        /** @var string $queueName */
        $queueName = $input->getArgument('queueName');

        $output->writeln(sprintf('Start handling queue with name "%s"...', $queueName));

        $callback = function (BaseMessageInterface $message) use ($queueName, $output): void {
            $event = new MessageEvent($message->getData());
            try {
                $this->eventDispatcher->dispatch($event, MessageEvent::NAME);
            } catch (\Throwable $exception) {
                $output->writeln(sprintf('An unexpected error occurred while processing the event, message "%s"', $exception->getMessage()));

                throw $exception;
            }

            $response = $event->getResponse();
            if (null !== $response) {
                try {
                    $this->messagingHandler->sendResponse($response, $queueName);
                } catch (\Throwable $exception) {
                    $output->writeln(sprintf('An unexpected error occurred while sending response, message "%s"', $exception->getMessage()));

                    throw $exception;
                }
            }
        };

        try {
            $this->messagingHandler->handleRequests($queueName, $callback);
        } catch (MessagingException $exception) {
            $output->writeln(sprintf('An unexpected error occurred, message "%s"', $exception->getMessage()));

            throw $exception;
        }

        return Command::SUCCESS;
    }
}
