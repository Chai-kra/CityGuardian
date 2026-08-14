<?php
function determineDepartment($issueType, $facilityType = null, $roadType = null, $floodSource = null) {
    switch ($issueType) {

        case 'pothole':
            if ($roadType === 'federal') {
                return 'JKR';
            }
            return 'DBKL Engineering Department';

        case 'broken_streetlight':
            if ($roadType === 'federal') {
                return 'JKR';
            }
            return 'DBKL Engineering Department';

        case 'illegal_dumping':
            return 'DBKL Solid Waste Management & Public Cleansing Department';

        case 'flooding':
            if ($floodSource === 'major_waterway') {
                return 'JPS';
            }
            return 'DBKL Engineering Department'; 

        case 'broken_trafficlight':
            if ($roadType === 'federal') {
                return 'JKR';
            }
            return 'DBKL Engineering Department';

        case 'damaged_public_facility':
            $facilityMap = [
                'sidewalk' => 'DBKL Engineering Department',
                'kerb' => 'DBKL Engineering Department',
                'guardrail' => 'DBKL Engineering Department',
                'bus_stop' => 'DBKL Engineering Department',
                'road_sign' => 'DBKL Engineering Department',
                'traffic_mirror' => 'DBKL Engineering Department',
                'public_toilet' => 'DBKL Health & Environment Department',
                'playground' => 'DBKL Landscape & Recreation Department',
                'park' => 'DBKL Landscape & Recreation Department',
                'bench' => 'DBKL Landscape & Recreation Department',
                'exercise_equipment' => 'DBKL Landscape & Recreation Department',
                'gazebo' => 'DBKL Landscape & Recreation Department',
            ];
            return $facilityMap[$facilityType] ?? 'DBKL Engineering Department';

        default:
            return 'DBKL Engineering Department';
    }
}
?>