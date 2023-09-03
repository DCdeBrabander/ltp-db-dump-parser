# Good day potential future colleagues

## Minor description
I've decided to use a Symfony Command setup just so you guys should be in familiar territory.

All the code you need is in:
- **src/Command/ParseDumpCommand.php**  
  Bootstrap command logic and print out to console

- **src/Csv/Parser.php**   
read from file, find and calculate rows in memory

- **src/Csv/Math.php**  
min/max/average logic for arrays

- **src/Csv/Exception.php**  
custom exceptions are more extensible, clear and future-proof

## usage

Command is registered in Symfony as `csv:parse`

```shell
foo@bar:~$ php bin/console csv:parse <path> <column> <select>
```
- path : path to CSV file (dump) to parse 
- column : what column to use
- select : the logic it should run on column 
  - by numerical index
  - by "type" (is column a 'score' or 'level')
  - by (supported) Math logic: "average", "lowest" or "highest"

```shell
# ex. Select column by numerical index
foo@bar:~$ php bin/console csv:parse path=./test_dump_1.csv Drive 1
>> Tante Test scored '3.4' on Drive 


# ex. get column's "type"
foo@bar:~$ php bin/console csv:parse path=./test_dump_2.csv MBOLevel type
>> The type for MBOLevel is level

# ex. Calculate 'lowest' value in column
foo@bar:~$ php bin/console csv:parse path=./test_dump_1.csv Consciousness lowest
>> The lowest score for Consciousness is 1.2

```

## setup
There is no web or any frontend component in this repository,   and it's all relatively simple and lightweight, so:
```shell
composer install
```
that's it..