<?php

namespace App\Service;

class CvAnalyzer
{
    public function analyze(string $text): array
    {
        return [
            'text_length' => strlen($text),
            'sections' => [
                'experience' => $this->findSection($text, [
                    'experience',
                    'work experience',
                    'professional experience',
                    'expérience'
                ]),

                'education' => $this->findSection($text, [
                    'education',
                    'formation',
                    'academic background'
                ]),

                'skills' => $this->findSection($text, [
                    'skills',
                    'technical skills',
                    'competences',
                    'compétences'
                ]),

                'languages' => $this->findSection($text, [
                    'languages',
                    'langues'
                ]),
            ]
        ];
    }

    private function findSection(string $text, array $keywords): bool
    {
        $text = strtolower($text);

        foreach ($keywords as $keyword) {
            if (str_contains($text, strtolower($keyword))) {
                return true;
            }
        }

        return false;
    }
}