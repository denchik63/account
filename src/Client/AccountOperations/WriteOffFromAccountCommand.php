<?php

namespace App\Client\AccountOperations;

use App\Server\Enum\EventNames;
use App\Server\Messaging\Exception\ExceptionInterface;
use App\Server\Messaging\Exception\StopHandlingException;
use App\Server\Messaging\MessagingInterface;
use App\Server\Messaging\Model\BaseMessageInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Uid\Uuid;

#[AsCommand(name: 'app:write-off-from-account')]
class WriteOffFromAccountCommand extends Command
{
    private const int DEFAULT_WAITING_RESPONSE_TIMEOUT = 30;

    public function __construct(private readonly MessagingInterface $messaging)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setDefinition([
            new InputArgument('queueName', InputArgument::REQUIRED),
            new InputArgument('accountId', InputArgument::REQUIRED),
            new InputArgument('sum', InputArgument::REQUIRED),
            new InputOption('responseTimeout', 't', InputOption::VALUE_OPTIONAL)
        ]);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $queueName = (string) $input->getArgument('queueName');
        $accountId = (string) $input->getArgument('accountId');
        $sum = (float) $input->getArgument('sum');
        $timeout = (int) ($input->getOption('responseTimeout') ?? self::DEFAULT_WAITING_RESPONSE_TIMEOUT);

        $output->writeln(sprintf('Writing-off from account with id "%s" on sum "%f"...', $accountId, $sum));

        $requestUid = Uuid::v7();
        $request = $this->createRequestData($requestUid, $accountId, $sum);

        $output->writeln(sprintf('Sending writing-off request with uid "%s"', $requestUid));

        try {
            $this->messaging->sendRequest($request, $queueName);
        } catch (ExceptionInterface $exception) {
            $output->writeln(sprintf('<error>Something went wrong while sending request, message "%s"</error>', $exception));

            return Command::FAILURE;
        }

        $output->writeln(sprintf('Request successfully sent, waiting for answer...'));

        try {
            $this->messaging->handleResponse($queueName, function (BaseMessageInterface $message) use ($output, $requestUid): void {
                $responseAsArray = json_decode($message->getData(), true, 512, JSON_THROW_ON_ERROR);
                $output->writeln(sprintf('Response from service is "%s"', var_export($responseAsArray, true)));

                if ($this->validateResponseData($responseAsArray, $requestUid)) {
                    if (empty($responseAsArray['success'])) {
                        $output->writeln('<error>Something went wrong</error>');
                    } else {
                        $output->writeln('<success>Account successfully wrote-off</success>');
                    }

                    throw new StopHandlingException();
                }
            }, $timeout);
        } catch (StopHandlingException) {

        } catch (ExceptionInterface $exception) {
            $output->writeln(sprintf('<error>Something went wrong while waiting response, message "%s"</error>', $exception));

            return Command::FAILURE;
        }


        return Command::SUCCESS;
    }

    private function createRequestData(string $requestUid, string $accountId, float $sum): string
    {
        return json_encode([
            'name' => EventNames::AccountWriteOff->value,
            'uid' => $requestUid,
            'account_id' => $accountId,
            'sum' => -$sum,
        ], JSON_THROW_ON_ERROR);
    }

    /** @param  array<string, mixed> $data */
    private function validateResponseData(array $data, string $requestUid): bool
    {
        return isset($data['event_uid']) && $data['event_uid'] === $requestUid;
    }
}