<?php

namespace App\Services;

class HomeownerParser
{
    // split on " and ", " & ", " + " (add more if needed)
    private const PERSON_SEPARATOR_REGEX = '/\s+and\s+|\s*&\s*|\s+\+\s+/i';

    // known titles on a best effort basis, add more if needed
    private const TITLE_REGEX = '/^(Mr|Mrs|Ms|Miss|Dr|Prof|Mister|Sir|Dame|Lord|Lady|Rev|Rev\.|Reverend)\s+(.*)$/i';

    // fallback if title regex doesn't match
    private const TITLE_FALLBACK_REGEX = '/^(\S+)\s+(\S.*)$/';

    // single letter, optional trailing period for initial
    private const INITIAL_REGEX = '/^([A-Za-z])\.?$/';

    // normalise input
    public function normaliseInput(string $raw): string
    {
        $s = trim(preg_replace('/\s+/', ' ', $raw));
        return $s;
    }

    // split a CSV row value into individual people strings using regex
    public function splitIntoPersons(string $raw): array
    {
        $raw = $this->normaliseInput($raw);
        if ($raw === '') {
            return [];
        }

        $persons = preg_split(self::PERSON_SEPARATOR_REGEX, $raw, -1, PREG_SPLIT_NO_EMPTY);
        return array_map(fn (string $s) => $this->normaliseInput($s), $persons);
    }

    // parse a single person string into title, firstname, initial, lastname
    public function parsePerson(string $person): array
    {
        $person = $this->normaliseInput($person);
        if ($person === '') {
            return [];
        }

        $result = [];

        if (preg_match(self::TITLE_REGEX, $person, $titleMatch)) {
            $result['title'] = $this->normaliseTitle($titleMatch[1]);
            $rest = trim($titleMatch[2] ?? '');
        } elseif (preg_match(self::TITLE_FALLBACK_REGEX, $person, $fallback)) {
            // unknown title (e.g. Lord, Rev, Sir) treat first word as title, rest as name
            $result['title'] = $fallback[1];
            $rest = trim($fallback[2] ?? '');
        } else {
            $result['title'] = $person;
            return $result;
        }

        if ($rest === '') {
            return $result;
        }

        $parts = preg_split('/\s+/', $rest, -1, PREG_SPLIT_NO_EMPTY);

        if (count($parts) === 1) {
            $result['lastname'] = $parts[0];
            return $result;
        }

        if (count($parts) === 2) {
            if ($this->looksLikeInitial($parts[0])) {
                $result['initial'] = trim($parts[0], '.');
                $result['lastname'] = $parts[1];
            } else {
                $result['firstname'] = $parts[0];
                $result['lastname'] = $parts[1];
            }
            return $result;
        }

        $lastname = array_pop($parts);
        $first = array_shift($parts);

        if ($this->looksLikeInitial($first) && count($parts) === 0) {
            $result['initial'] = trim($first, '.');
            $result['lastname'] = $lastname;
        } else {
            $result['firstname'] = $first . (count($parts) > 0 ? ' ' . implode(' ', $parts) : '');
            $result['lastname'] = $lastname;
        }

        return $result;
    }

    // parse a full row (may contain multiple people) and return an array of homeowner array(s)
    public function parseRow(string $row): array
    {
        $persons = $this->splitIntoPersons($row);
        $homeowners = [];
        $carry = [];

        // process in reverse so "Mr and Mrs Smith" we see "Mrs Smith" first and carry "Smith" to "Mr"
        $persons = array_reverse($persons);

        foreach ($persons as $personStr) {
            $parsed = $this->parsePerson($personStr);

            if (isset($parsed['lastname'])) {
                $carry['lastname'] = $parsed['lastname'];
            }
            if (isset($parsed['firstname'])) {
                $carry['firstname'] = $parsed['firstname'];
            }
            if (isset($parsed['initial'])) {
                $carry['initial'] = $parsed['initial'];
            }

            if (count($parsed) === 1 && isset($parsed['title'])) {
                $merged = ['title' => $parsed['title']];
                if (!empty($carry['lastname'])) {
                    $merged['lastname'] = $carry['lastname'];
                }
                if (!empty($carry['firstname'])) {
                    $merged['firstname'] = $carry['firstname'];
                }
                if (!empty($carry['initial'])) {
                    $merged['initial'] = $carry['initial'];
                }
                $homeowners[] = $merged;
            } else {
                $homeowners[] = $parsed;
            }
        }

        return array_reverse($homeowners);
    }

    private function normaliseTitle(string $title): string
    {
        return preg_match('/^Mister$/i', $title) ? 'Mr' : $title;
    }

    
    private function looksLikeInitial(string $token): bool
    {
        return (bool) preg_match(self::INITIAL_REGEX, trim($token, '.'));
    }
}
