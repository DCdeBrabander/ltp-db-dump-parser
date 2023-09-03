<?php

namespace App\Csv;

class Parser
{
    /**
     * header-row of csv.
     * @see read()
     */
    private array $headers = [];

    /**
     * Data-rows of csv.
     * @see read()
     */
    private array $rows = [];

    /**
     * "Cache" last result.
     */
    private int|float|string|null $lastResult;

    /**
     * What type of query is user running.
     * numerical index
     */
    private int|string $selectedQueryType;

    /**
     * Keep track of which column we are working with.
     * @see find() / useColumn()
     */
    private ?string $selectedColumn = null;
    private array $selectedColumnData = [];

    /**
     * Remember how many columns a row should have.
     * Determined by counting headers.
     */
    private int $columnCount = 0;

    /**
     * Simple way of mapping implemented 'Math' functions to human-readable / required aliases.
     * Given by end-user, used on column-data.
     */
    private array $supportedMathQueries = [
        'lowest' => 'App\Csv\Math::min',
        'highest' => 'App\Csv\Math::max',
        'average' => 'App\Csv\Math::average',
    ];

    public function __construct(private string $pathToFile, private string $delimiter = ";")
    {
        if (!strlen($pathToFile)) {
            throw new CsvException("Invalid path given.");
        }

        if (!file_exists($this->pathToFile)) {
            throw new CsvException("File not found.");
        }

        return $this;
    }

    /**
     * Nothing much special here..
     * Relatively standard code for looping through csv file.
     */
    public function read()
    {
        $handle = fopen($this->pathToFile, "r");

        if (!$handle) {
            throw new CsvException("Unable to open file.");
            return $this;
        }

        $currentRow = 1;
        while (($row = fgetcsv($handle, 9999, $this->delimiter)) !== false) {
            // Keep track of headers on first row, so we can use mapped arrays later.
            if ($currentRow == 1) {
                $this->columnCount = count($row);
                $this->headers = $row;
            } else {
                // We could throw CsvMalformedException but for now we just carry on..
                if (count($row) !== $this->columnCount) {
                    $this->log(
                        "Found invalid column count '" . count($row) . "'"
                        . " (expected is '{$this->columnCount}')"
                        . PHP_EOL
                        . implode($this->delimiter, $row)
                    );
                    continue;
                }
                $this->rows[] = array_combine($this->headers, $row);
            }
            $currentRow++;
        }

        fclose($handle);
        return $this;
    }

    /**
     * Instead of having to use seperate useColumn() and select() methods.
     * we might as well create one find() method.
     * @param string $select (Required)
     * @param string $column (Optional)
     *
     * @example find('highest', 'Enthousiasm')
     */
    public function find(int|string $select = null, string $column = null): string|float|int|null
    {
        $this->selectedColumn ??= $column;
        $this->selectedQueryType ??= $select;

        $this->selectedColumnData = array_column($this->rows, $this->selectedColumn);

        if (empty($this->selectedQueryType)) {
            throw new CsvException("No selection type set...");
            return null;
        }

        // if (empty($this->selectedColumnData)) {
        //     throw new CsvException("No data in selected column...");
        //     return null;
        // }

        // We are simply selecting by 'index' (aka row number)
        if (is_numeric($this->selectedQueryType)) {
            $result = $this->selectedColumnData[$this->getNormalizedIndex()] ?? null;

        // Determine 'type' by checking first occurrence if numerical or not.
        // score = any float or integer
        // level = A, B, C, ...
        } elseif ($this->selectedQueryType == 'type') {
            $value = $this->rows[0][$column] ?? null;
            $result = is_numeric($value) ? 'score' : 'level';

        // It's not numerical index nor type, so we assume (for now) it's a calculation.
        } else {
            $result = $this->calculateForColumn(
                $this->selectedQueryType,
                $this->selectedColumnData
            );
        }

        $this->storeLastResult($result);
        return $result;
    }

    /**
     * The "query" (aka 'what') we want to perform on dataset.
     * @param string|int $select
     */
    public function calculateForColumn(string $queryType, array $columnData): int|float|string|null
    {
        if (empty($columnData)) {
            return null;
        }

        if (!in_array($queryType, array_keys($this->supportedMathQueries))) {
            return null;
        }

        return call_user_func($this->supportedMathQueries[$queryType], $columnData);
    }

    /**
     * Optional way of setting column before running find().
     * @example Parser->useColumn()->find()
     */
    public function useColumn(string $column): self
    {
        $this->selectedColumn = $column;
        return $this;
    }

    /**
     * Optional way of setting 'selection-type' before running find().
     * @example Parser->useSelectType()->useColumn()->find()
     */
    public function useSelectType(string $select): self
    {
        $this->selectedQueryType = $select;
        return $this;
    }

    /**
     * Currently only use for getting Participant's name outside of this parser class.
     */
    public function getValueOfColumnAtIndex(string $columnName, int $index): int|float|string|null
    {
        if (empty($this->rows)) {
            return null;
        }
        return $this->rows[$index][$columnName] ?? null;
    }

    protected function getLastResult(): null|string|int|float
    {
        return $this->lastResult;
    }

    private function storeLastResult($result): void
    {
        $this->lastResult = $result;
    }

    private function log($message): void
    {
        echo "Log: $message" . PHP_EOL;
    }

    private function getNormalizedIndex(): int
    {
        if (is_null($this->selectedQueryType)) {
            return 0;
        }
        return (int) $this->selectedQueryType - 1;
    }
}
