<?php

namespace Tests\Unit\Services;

use App\Services\HomeownerParser;
use Mockery;
use Tests\TestCase as LaravelTestCase;

class HomeownerParserTest extends LaravelTestCase
{
    private HomeownerParser $parser;

    protected function setUp(): void
    {
        parent::setUp();
        $this->parser = new HomeownerParser();
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public static function sampleDataProvider(): array
    {
        return [
            'Mr John Smith' => [
                'input' => 'Mr John Smith',
                'expected' => [
                    ['title' => 'Mr', 'firstname' => 'John', 'lastname' => 'Smith'],
                ],
            ],
            'Mrs Jane Smith' => [
                'input' => 'Mrs Jane Smith',
                'expected' => [
                    ['title' => 'Mrs', 'firstname' => 'Jane', 'lastname' => 'Smith'],
                ],
            ],
            'Mister John Doe' => [
                'input' => 'Mister John Doe',
                'expected' => [
                    ['title' => 'Mr', 'firstname' => 'John', 'lastname' => 'Doe'],
                ],
            ],
            'Mr Bob Lawblaw' => [
                'input' => 'Mr Bob Lawblaw',
                'expected' => [
                    ['title' => 'Mr', 'firstname' => 'Bob', 'lastname' => 'Lawblaw'],
                ],
            ],
            'Mr and Mrs Smith' => [
                'input' => 'Mr and Mrs Smith',
                'expected' => [
                    ['title' => 'Mr', 'lastname' => 'Smith'],
                    ['title' => 'Mrs', 'lastname' => 'Smith'],
                ],
            ],
            'Mr Craig Charles' => [
                'input' => 'Mr Craig Charles',
                'expected' => [
                    ['title' => 'Mr', 'firstname' => 'Craig', 'lastname' => 'Charles'],
                ],
            ],
            'Mr M Mackie' => [
                'input' => 'Mr M Mackie',
                'expected' => [
                    ['title' => 'Mr', 'initial' => 'M', 'lastname' => 'Mackie'],
                ],
            ],
            'Mrs Jane McMaster' => [
                'input' => 'Mrs Jane McMaster',
                'expected' => [
                    ['title' => 'Mrs', 'firstname' => 'Jane', 'lastname' => 'McMaster'],
                ],
            ],
            'Mr Tom Staff and Mr John Doe' => [
                'input' => 'Mr Tom Staff and Mr John Doe',
                'expected' => [
                    ['title' => 'Mr', 'firstname' => 'Tom', 'lastname' => 'Staff'],
                    ['title' => 'Mr', 'firstname' => 'John', 'lastname' => 'Doe'],
                ],
            ],
            'Dr P Gunn' => [
                'input' => 'Dr P Gunn',
                'expected' => [
                    ['title' => 'Dr', 'initial' => 'P', 'lastname' => 'Gunn'],
                ],
            ],
            'Dr & Mrs Joe Bloggs' => [
                'input' => 'Dr & Mrs Joe Bloggs',
                'expected' => [
                    ['title' => 'Dr', 'firstname' => 'Joe', 'lastname' => 'Bloggs'],
                    ['title' => 'Mrs', 'firstname' => 'Joe', 'lastname' => 'Bloggs'],
                ],
            ],
            'Ms Claire Robbo' => [
                'input' => 'Ms Claire Robbo',
                'expected' => [
                    ['title' => 'Ms', 'firstname' => 'Claire', 'lastname' => 'Robbo'],
                ],
            ],
            'Prof Alex Brogan' => [
                'input' => 'Prof Alex Brogan',
                'expected' => [
                    ['title' => 'Prof', 'firstname' => 'Alex', 'lastname' => 'Brogan'],
                ],
            ],
            'Mrs Faye Hughes-Eastwood' => [
                'input' => 'Mrs Faye Hughes-Eastwood',
                'expected' => [
                    ['title' => 'Mrs', 'firstname' => 'Faye', 'lastname' => 'Hughes-Eastwood'],
                ],
            ],
            'Mr F. Fredrickson' => [
                'input' => 'Mr F. Fredrickson',
                'expected' => [
                    ['title' => 'Mr', 'initial' => 'F', 'lastname' => 'Fredrickson'],
                ],
            ],
        ];
    }

    /** @dataProvider sampleDataProvider */
    public function testParsingSampleData(string $input, array $expected): void
    {
        $result = $this->parser->parseRow($input);

        $this->assertSameSize($expected, $result, 'Parsed person count should match expected');

        foreach ($expected as $index => $expectedPerson) {
            $this->assertArrayHasKey($index, $result);
            foreach ($expectedPerson as $key => $value) {
                $this->assertArrayHasKey($key, $result[$index], "Missing key '{$key}' at index {$index}");
                $this->assertSame($value, $result[$index][$key], "Value for '{$key}' at index {$index}");
            }
        }
    }

    public function testNormalInputWithWhitespace(): void
    {
        $this->assertSame('Mr John Smith', $this->parser->normaliseInput("  Mr   John   Smith  "));
    }

    public function testSplitWithAnd(): void
    {
        $persons = $this->parser->splitIntoPersons('Mr Tom Staff and Mr John Doe');
        $this->assertSame(['Mr Tom Staff', 'Mr John Doe'], $persons);
    }

    public function testSplitWithAmpersands(): void
    {
        $persons = $this->parser->splitIntoPersons('Dr & Mrs Joe Bloggs');
        $this->assertSame(['Dr', 'Mrs Joe Bloggs'], $persons);
    }

    public function testParsingWithJustTitle(): void
    {
        $result = $this->parser->parsePerson('Dr');
        $this->assertSame(['title' => 'Dr'], $result);
    }

    public function testEmptyInput(): void
    {
        $this->assertSame([], $this->parser->parseRow(''));
        $this->assertSame([], $this->parser->splitIntoPersons('   '));
    }
}
