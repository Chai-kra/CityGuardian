<?php
require_once "db.php"; // gives us $geminiApiKey

function classifyIssue($imagePath) {
    global $geminiApiKey;

    $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key=$geminiApiKey";

    $imageData = base64_encode(file_get_contents($imagePath));
    $mimeType = mime_content_type($imagePath);

    $sysInstruct = <<<SYS
You are CityGuardian in Malaysia. Your main goal is to identify the issue in the image.
There are 5 possible issues: potholes, broken_streetlight, illegal_dumping, flooding, damaged_public_facility.
There are 4 priority levels, depending on how severe the issue appears in the image: critical, high, medium, low.

Also provide a confidence score between 0 and 1 representing how certain you are about this classification,
based on image clarity, how typical the issue looks, and whether multiple issue types could plausibly apply.

Respond ONLY with valid JSON in this exact format, nothing else:
{
  "issue": "pothole" | "broken_streetlight" | "illegal_dumping" | "flooding" | "damaged_public_facility",
  "priority": "critical" | "high" | "medium" | "low",
  "description": "Simple description of what you see in the image",
  "confidence": 0.0 to 1.0
}
SYS;

    $data = [
        "system_instruction" => [
            "parts" => [["text" => $sysInstruct]]
        ],
        "contents" => [
            [
                "role" => "user",
                "parts" => [
                    ["text" => "Analyze this image and classify the issue:"],
                    [
                        "inline_data" => [
                            "mime_type" => $mimeType,
                            "data" => $imageData
                        ]
                    ]
                ]
            ]
        ]
    ];

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/json"]);

    $response = curl_exec($ch);

    if (curl_errno($ch)) {
        $error = curl_error($ch);
        curl_close($ch);
        return ["error" => $error];
    }
    curl_close($ch);

    $decoded = json_decode($response, true);
    $text = $decoded['candidates'][0]['content']['parts'][0]['text'] ?? null;

    if (!$text) {
        return ["error" => "No response text", "raw" => $decoded];
    }

    $text = preg_replace('/```json|```/', '', $text);
    $result = json_decode(trim($text), true);

    if (json_last_error() !== JSON_ERROR_NONE) {
        return ["error" => "Could not parse AI response", "raw_text" => $text];
    }

    return ["success" => true, "data" => $result];
}
?>