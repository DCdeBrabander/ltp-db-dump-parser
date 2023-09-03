<?php

namespace App\Command;

use App\Csv\CsvException;
use App\Csv\Parser as CsvParser;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\OutputInterface;

// the name of the command is what users type after "php bin/console"
#[AsCommand(name: 'csv:parse', description: "Parse a CSV")]
class ParseDumpCommand extends Command
{
    private string $rootPath;

    private CsvParser $parser;

    public function __construct(string $rootPath)
    {
        $this->rootPath = $rootPath;
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument(
            "path",
            InputArgument::REQUIRED,
            "Path to CSV file to parse."
        );

        $this->addArgument(
            "column",
            InputArgument::REQUIRED,
            "Column you want to select."
        );

        $this->addArgument(
            "select",
            InputArgument::REQUIRED,
            "Row to select or one of following methods: 'lowest', 'highest', 'averate', 'type'"
        );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $logger = new ConsoleLogger($output);

        $path = $this->rootPath . "/" . $this->getValueOfArgument($input->getArgument("path"));
        $column = $this->getValueOfArgument($input->getArgument("column"));
        $select = $this->getValueOfArgument($input->getArgument("select"));

        try {
            $this->parser = new CsvParser($path, ";");

            $result = $this->parser->read()->find(
                $select,    // the 'how'/'who', for ex: '1' or 'highest'
                $column,    // the 'what', for ex.: "Drive" or "Enthousiasm"
            );

            // You have result now, but assigment requires some human-readable text so:
            echo $this->verboseResult($result, $select, $column);

            return Command::SUCCESS;
        } catch (CsvException $exception) {
            echo $exception->getMessage();
            return Command::FAILURE;
        }
    }

    /**
     * Use generated result and given arguments to write more human-friendly message.
     */
    public function verboseResult($result, string $select, string $column): string
    {
        if ($select == "type") {
            $message = $this->verboseTypeQueryResult($result, $column);
        } elseif (is_numeric($select)) {
            $message = $this->verboseIndexQueryResult($result, $column, $select);
        } else {
            $message = $this->verboseCalcQueryResult($result, $column, $select);
        }
        return ">> $message \r\n";
    }

    private function verboseTypeQueryResult(int|float|string|null $result, string $column): string
    {
        return "The type for {$column} is {$result}";
    }

    private function verboseIndexQueryResult(int|float|string|null $result, string $column, int $index, string $nameKey = 'Participant'): string
    {
        $name = $this->parser->getValueOfColumnAtIndex($nameKey, $index - 1) ?? "NO_NAME";
        if (empty($result)) {
            return "$name has no score for $column";
        }
        return "$name scored '$result' on $column";
    }

    private function verboseCalcQueryResult(int|float|string|null $result, string $column, string $select): string
    {
        if (empty($result)) {
            return "Not able to calculate {$select} score for $column";
        }
        return "The {$select} score for {$column} is {$result}";
    }

    /**
     * Support multiple use-cases of passing arguments to Command.
     * @example path=./some_file.csv
     * @example ./some_file.csv
     */
    private function getValueOfArgument(string $argument, string $argumentDelimiter = "=")
    {
        $argumentParts = explode($argumentDelimiter, $argument);

        if (count($argumentParts) == 1) {
            return $argument;
        }

        return $argumentParts[1];
    }
}
