<?php

namespace App\Http\Controllers;

use App\Services\HomeownerParser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class CSVController extends Controller
{
    public function __construct(
        private HomeownerParser $parser
    ) {}

    public function index()
    {
        return view('upload', ['homeowners' => []]);
    }

    // predicted headers, add more if needed (assuming this would be a tailored controller in production)
    private const HEADER_NAMES = ['homeowner', 'homeowners', 'name', 'names', 'owner', 'owners', 'occupant', 'occupants'];

    public function upload(Request $request)
    {
        $request->validate([
            'csv_file' => 'required|mimes:csv,txt|max:2048',
        ]);

        $file = $request->file('csv_file');
        $content = file_get_contents($file->getRealPath());
        $content = preg_replace('/\r\n|\r/', "\n", $content);
        $rows = array_map('str_getcsv', explode("\n", $content));

        $homeowners = [];
        $first = true;

        foreach ($rows as $row) {
            $cell = $this->firstNonEmptyCell($row);
            if ($cell === '') {
                continue;
            }

            $cell = $this->parser->normaliseInput($cell);

            if ($first && $this->looksLikeHeader($cell)) {
                $first = false;
                continue;
            }
            $first = false;

            $persons = $this->parser->parseRow($cell);
            if ($persons === []) {
                continue;
            }

            $linked = count($persons) > 1;
            $linkedDisplay = $linked ? $cell : null;

            foreach ($persons as $person) {
                $person['linked'] = $linked;
                if ($linkedDisplay !== null) {
                    $person['linked_display'] = $linkedDisplay;
                }
                $homeowners[] = $person;
            }
        }

        /// uncomment to log the data to the testing channel
        // Log::channel('testing')->info('Uploaded CSV Data: ', ['data' => $homeowners]);

        return response()->json(['homeowners' => $homeowners]);
    }

    private function firstNonEmptyCell(array $row): string
    {
        foreach ($row as $value) {
            $s = trim((string) $value);
            if ($s !== '') {
                return $s;
            }
        }
        return '';
    }

    private function looksLikeHeader(string $cell): bool
    {
        $lower = strtolower(trim($cell));
        return in_array($lower, self::HEADER_NAMES, true);
    }
}
