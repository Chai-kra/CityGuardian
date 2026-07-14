<?php
require_once "db.php"; // gives us $geminiApiKey

function classifyIssue($imagePath, $userDescription = '', $userLocation = '') {
    global $geminiApiKey;

    $url = "https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash:generateContent?key=$geminiApiKey";

    $imageData = base64_encode(file_get_contents($imagePath));
    $mimeType = mime_content_type($imagePath);

    $sysInstruct = <<<SYS
        You are CityGuardian in Malaysia. Your main goal is to identify the issue from the image and the user's description of the issue.
        There are 6 possible issues: pothole, broken_streetlight, illegal_dumping, flooding, broken_trafficlight, damaged_public_facility.
        There are 4 priority levels, depending on how severe the issue appears in the image: critical, high, medium, low.

        Also provide a confidence score between 0 and 1 representing how certain you are about this classification,
        based on the user's description, image clarity, how typical the issue looks, and whether multiple issue types could plausibly apply.

        Respond ONLY with valid JSON in this exact format, nothing else:
        {
        "issue": "pothole" | "broken_streetlight" | "illegal_dumping" | "flooding" | "broken_trafficlight" | "damaged_public_facility",
        "facility_type": "sidewalk" | "kerb" | "guardrail" | "bus_stop" | "road_sign" | "traffic_mirror" | "public_toilet" | "playground" | "park" | "bench" | "exercise_equipment" | "gazebo" | null,
        "road_type": "federal" | "other" | null,
        "flood_source": "major_waterway" | "local_drain" | null,
        "priority": "critical" | "high" | "medium" | "low",
        "description": "One paragraph of what you see in the image, the user's description, and location of the issue",
        "confidence": 0.0 to 1.0
        }
    SYS;

    $knowledge = <<<KNOWLEDGE
        pothole = a deep hole in a road. It forms when heavy cars and bad weather break down the pavement over time.
        Sometimes there might be some water in the potholes if the picture is taken after a rain.
        Uneven roads are also considered as potholes (in this case, set "priority" as "low")

        broken_streetlight = a road light that does not work. It stays dark at night. This makes the streets unsafe for people.
        It can also be that the streetlight is leaning to one side.

        illegal_dumping = throwing away trash in places where it does not belong, such as in forests, empty lots,
        or on roadsides, instead of using official trash bins or city dumps.

        flooding = when water covers land that is usually dry.

        broken_trafficlight = A broken or malfunctioning traffic light is any signal that fails to display its standard stop, 
        caution, or go sequence. This includes completely dark intersections, stuck lights, or signals flashing red or yellow in all directions. 

        damaged_public_facility = a broken or ruined place and service that the government provides for everyone to use.


        If the issue is "pothole", "broken_streetlight" or "broken_trafficlight", identify the "road_type" from this link: 
        https://www.kkr.gov.my/sites/default/files/2022-10/JUPSemenanjungMalaysia.pdf
        Road names that appear in this link are federal roads, so set "road_type" as "federal", while road name that does not appear here are other types of roads, so set "road_type" as "other".
        If the issue is not "pothole", "broken_streetlight" or "broken_trafficlight", set "road_type" to null.

        If the issue is "illegal_dumping", set "priority" to "low".

        If the issue is "flooding", identify the "flood_source" based on the image:
        - "major_waterway" if the flooding involves a river, stream, or major drainage channel overflowing
        - "local_drain" if the flooding is from a clogged roadside drain, monsoon drain, or localized ponding on roads/residential areas with no visible river/major channel involved
        If the issue is not "flooding", set "flood_source" to null.
        If the issue is "flooding", set "priority" according to how high the water level goes.

        If the issue is "damaged_public_facility", identify the "facility_type" from this list:
        sidewalk, kerb, guardrail, bus_stop, road_sign, traffic_mirror, public_toilet, playground, park, bench, exercise_equipment, gazebo.
        If the issue is not "damaged_public_facility", set "facility_type" to null.
        If the issue is "damaged_public_facility", set "priority" to low unless the issue can hurt people.

        If the issue is "broken_trafficlight", set "priority" to "critical".

        If the issue is "broken_streetlight", set "priority" according to how serious is the issue. 
        If the road appears to be very dark because the streetlight is not working, set "priority" as high.
        If there is only one streetlight broken among many functioning streetlights, set "priority" as "low".
        If there is are two or more streetlight broken among many functioning streetlights, set "priority" as "medium".
        If the streetlight leans a little, set "priority" to "low".
        If the streetlight leans a lot until it lies on the ground, set "priority" to "critical".

        If the issue is "pothole", set "priority" according to whether the pothole may hurt people.
        If the pothole is so big that people can fall into it, set "priority" to "critical".
        If the pothole is small, set "priority" to "medium".
        If it is just uneven road, set "priority" to "low".

    KNOWLEDGE;

    $userText = "Analyze this image and classify the issue.\n";
    $userText .= $knowledge . "\n\n";
    $userText .= "User's description: " . ($userDescription !== '' ? $userDescription : "(none provided)") . "\n";
    $userText .= "Location: " . ($userLocation !== '' ? $userLocation : "(none provided)");

    $data = [
        "system_instruction" => [
            "parts" => [["text" => $sysInstruct]]
        ],
        "contents" => [
            [
                "role" => "user",
                "parts" => [
                    ["text" => $userText],
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