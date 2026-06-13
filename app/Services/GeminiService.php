<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeminiService
{
    public function screenResume(string $resumePath, string $jobTitle, string $jobDescription, string $jobRequirements): ?array
    {
        $apiKey = config('services.gemini.key');
        if (!$apiKey) {
            Log::error('Gemini API key is not configured.');
            return null;
        }

        // Determine absolute file path and mime type
        $fullPath = storage_path('app/public/' . $resumePath);
        if (!file_exists($fullPath)) {
            Log::error("Resume file not found at: {$fullPath}");
            return null;
        }

        $mimeType = mime_content_type($fullPath);
        $fileData = file_get_contents($fullPath);

        $prompt = "You are an expert HR recruitment assistant. Evaluate the following resume against the job posting details.\n\n" .
                  "Job Title: {$jobTitle}\n" .
                  "Job Description: {$jobDescription}\n" .
                  "Job Requirements: {$jobRequirements}\n\n" .
                  "Analyze the candidate's suitability and score them on a scale of 0 to 100, where:\n" .
                  "- 0-39: Poor match / Unsuitable\n" .
                  "- 40-59: Mediocre match\n" .
                  "- 60-79: Good match\n" .
                  "- 80-100: Excellent match\n\n" .
                  "Provide a summary feedback (maximum 2-3 sentences) explaining the score, highlighting the key reasons for the match or mismatch.\n\n" .
                  "You must respond in JSON format conforming to this schema:\n" .
                  "{\n" .
                  "  \"score\": integer,\n" .
                  "  \"feedback\": string\n" .
                  "}";

        $parts = [];
        $parts[] = ['text' => $prompt];

        if ($mimeType === 'application/vnd.openxmlformats-officedocument.wordprocessingml.document') {
            // It's a DOCX file, extract plain text and append it as text
            $text = $this->extractTextFromDocx($fullPath);
            $parts[] = ['text' => "Resume Content (Extracted from Word Doc):\n\n" . $text];
        } else {
            // Send as inlineData (handles PDF, JPEG, PNG, WEBP, etc.)
            $parts[] = [
                'inlineData' => [
                    'mimeType' => $mimeType,
                    'data' => base64_encode($fileData)
                ]
            ];
        }

        $payload = [
            'contents' => [
                [
                    'parts' => $parts
                ]
            ],
            'generationConfig' => [
                'responseMimeType' => 'application/json',
                'responseSchema' => [
                    'type' => 'OBJECT',
                    'properties' => [
                        'score' => ['type' => 'INTEGER'],
                        'feedback' => ['type' => 'STRING']
                    ],
                    'required' => ['score', 'feedback']
                ]
            ]
        ];

        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
            ])->post("https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key={$apiKey}", $payload);

            if ($response->failed()) {
                Log::error('Gemini API call failed: ' . $response->body());
                return null;
            }

            $result = $response->json();
            $textResponse = $result['candidates'][0]['content']['parts'][0]['text'] ?? null;

            if ($textResponse) {
                return json_decode($textResponse, true);
            }
        } catch (\Exception $e) {
            Log::error('Error calling Gemini API: ' . $e->getMessage());
        }

        return null;
    }

    public function chat(array $messages, string $systemInstruction): ?string
    {
        $apiKey = config('services.gemini.key');
        if (!$apiKey) {
            Log::error('Gemini API key is not configured.');
            return null;
        }

        $payload = [
            'system_instruction' => [
                'parts' => [
                    ['text' => $systemInstruction]
                ]
            ],
            'contents' => $messages,
        ];

        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
            ])->post("https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key={$apiKey}", $payload);

            if ($response->failed()) {
                Log::error('Gemini API call failed: ' . $response->body());
                return null;
            }

            $result = $response->json();
            return $result['candidates'][0]['content']['parts'][0]['text'] ?? null;
        } catch (\Exception $e) {
            Log::error('Error calling Gemini API: ' . $e->getMessage());
        }

        return null;
    }

    private function extractTextFromDocx(string $filePath): string
    {
        $zip = new \ZipArchive();
        if ($zip->open($filePath) === true) {
            if (($index = $zip->locateName('word/document.xml')) !== false) {
                $data = $zip->getFromIndex($index);
                $zip->close();
                return strip_tags($data);
            }
            $zip->close();
        }
        return '';
    }
}
