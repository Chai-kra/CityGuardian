<?php

function normalizeRoutingValue($value)
{
    $value = strtolower(trim((string) $value));
    return $value === '' ? null : $value;
}

function determineDepartmentCode(
    $issueType,
    $facilityType = null,
    $roadType = null,
    $floodSource = null
) {
    $issueType = normalizeRoutingValue($issueType) ?? 'general';
    $facilityType = normalizeRoutingValue($facilityType);
    $roadType = normalizeRoutingValue($roadType);
    $floodSource = normalizeRoutingValue($floodSource);

    switch ($issueType) {
        case 'pothole':
        case 'broken_streetlight':
        case 'broken_trafficlight':
            return $roadType === 'federal'
                ? 'JKR-ROADS'
                : 'DBKL-CIVIL';

        case 'illegal_dumping':
            return 'DBKL-SOLID-WASTE';

        case 'flooding':
            return $floodSource === 'major_waterway'
                ? 'JPS-WATER'
                : 'DBKL-CIVIL';

        case 'damaged_public_facility':
            $facilityMap = [
                'public_toilet' => 'DBKL-HEALTH',

                'playground' => 'DBKL-LANDSCAPE',
                'park' => 'DBKL-LANDSCAPE',
                'bench' => 'DBKL-LANDSCAPE',
                'exercise_equipment' => 'DBKL-LANDSCAPE',
                'gazebo' => 'DBKL-LANDSCAPE',

                'sidewalk' => 'DBKL-CIVIL',
                'kerb' => 'DBKL-CIVIL',
                'guardrail' => 'DBKL-CIVIL',
                'bus_stop' => 'DBKL-CIVIL',
                'road_sign' => 'DBKL-CIVIL',
                'traffic_mirror' => 'DBKL-CIVIL'
            ];

            return $facilityMap[$facilityType] ?? 'DBKL-CIVIL';

        default:
            return 'DBKL-CIVIL';
    }
}

/*
 * Temporary compatibility function.
 * Old PHP pages can still display the department name.
 * New routing code should use determineDepartmentCode().
 */
function determineDepartment(
    $issueType,
    $facilityType = null,
    $roadType = null,
    $floodSource = null
) {
    $departmentNames = [
        'DBKL-CIVIL' => 'DBKL Engineering Department',
        'DBKL-HEALTH' => 'DBKL Health & Environment Department',
        'DBKL-LANDSCAPE' => 'DBKL Landscape & Recreation Department',
        'DBKL-SOLID-WASTE' => 'DBKL Solid Waste Management & Public Cleansing Department',
        'JKR-ROADS' => 'JKR',
        'JPS-WATER' => 'JPS'
    ];

    $code = determineDepartmentCode(
        $issueType,
        $facilityType,
        $roadType,
        $floodSource
    );

    return $departmentNames[$code] ?? 'DBKL Engineering Department';
}
?>