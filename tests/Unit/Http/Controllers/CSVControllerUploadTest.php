<?php

namespace Tests\Unit\Http\Controllers;

use App\Http\Controllers\CSVController;
use App\Services\HomeownerParser;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Mockery;
use Tests\TestCase;

class CSVControllerUploadTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    // test upload and skip header row
    public function testUpload(): void
    {
        $csvContent = "homeowner,\nMr John Smith,\nMrs Jane Smith,\n";
        $tempFile = tmpfile();
        $tempPath = stream_get_meta_data($tempFile)['uri'];
        fwrite($tempFile, $csvContent);
        fseek($tempFile, 0);

        $uploadedFile = new UploadedFile(
            $tempPath,
            'test.csv',
            'text/csv',
            null,
            true
        );

        $request = Request::create('/upload', 'POST', [], [], [
            'csv_file' => $uploadedFile,
        ]);
        $request->headers->set('Content-Type', 'multipart/form-data');

        $controller = new CSVController(new HomeownerParser());
        $response = $controller->upload($request);

        $this->assertSame(200, $response->getStatusCode());
        $data = $response->getData(true);
        $this->assertArrayHasKey('homeowners', $data);
        $homeowners = $data['homeowners'];

        $this->assertCount(2, $homeowners, 'Header row should be skipped; two data rows only');
        $this->assertEquals(
            ['title' => 'Mr', 'firstname' => 'John', 'lastname' => 'Smith', 'linked' => false],
            array_intersect_key($homeowners[0], array_flip(['title', 'firstname', 'lastname', 'linked']))
        );
        $this->assertEquals(
            ['title' => 'Mrs', 'firstname' => 'Jane', 'lastname' => 'Smith', 'linked' => false],
            array_intersect_key($homeowners[1], array_flip(['title', 'firstname', 'lastname', 'linked']))
        );

        fclose($tempFile);
    }

    // test upload with linked persons
    public function testUploadWithLinkedPersons(): void
    {
        $csvContent = "homeowner,\nMr and Mrs Smith,\n";
        $tempFile = tmpfile();
        $tempPath = stream_get_meta_data($tempFile)['uri'];
        fwrite($tempFile, $csvContent);
        fseek($tempFile, 0);

        $uploadedFile = new UploadedFile(
            $tempPath,
            'test.csv',
            'text/csv',
            null,
            true
        );

        $request = Request::create('/upload', 'POST', [], [], [
            'csv_file' => $uploadedFile,
        ]);

        $controller = new CSVController(new HomeownerParser());
        $response = $controller->upload($request);

        $data = $response->getData(true);
        $homeowners = $data['homeowners'];

        $this->assertCount(2, $homeowners);
        foreach ($homeowners as $person) {
            $this->assertTrue($person['linked'], 'Both Mr and Mrs Smith should be marked linked');
            $this->assertSame('Mr and Mrs Smith', $person['linked_display']);
        }

        fclose($tempFile);
    }

    // test upload and add a linked user
    public function testUploadAddLinkedUser(): void
    {
        $csvContent = "homeowner,\nMr and Mrs Smith,\n";
        $tempFile = tmpfile();
        $tempPath = stream_get_meta_data($tempFile)['uri'];
        fwrite($tempFile, $csvContent);
        fseek($tempFile, 0);

        $uploadedFile = new UploadedFile(
            $tempPath,
            'test.csv',
            'text/csv',
            null,
            true
        );

        $request = Request::create('/upload', 'POST', [], [], [
            'csv_file' => $uploadedFile,
        ]);

        $parser = Mockery::mock(HomeownerParser::class);
        $parser->shouldReceive('normaliseInput')
            ->with(Mockery::type('string'))
            ->andReturnUsing(fn ($s) => trim(preg_replace('/\s+/', ' ', $s)));
        $parser->shouldReceive('parseRow')
            ->with('Mr and Mrs Smith')
            ->once()
            ->andReturn([
                ['title' => 'Mr', 'lastname' => 'Smith'],
                ['title' => 'Mrs', 'lastname' => 'Smith'],
            ]);

        $controller = new CSVController($parser);
        $response = $controller->upload($request);

        $data = $response->getData(true);
        $this->assertCount(2, $data['homeowners']);
        $this->assertTrue($data['homeowners'][0]['linked']);
        $this->assertSame('Mr and Mrs Smith', $data['homeowners'][0]['linked_display']);
        $this->assertSame('Mr', $data['homeowners'][0]['title']);
        $this->assertSame('Smith', $data['homeowners'][0]['lastname']);

        fclose($tempFile);
    }
}
