<?php
include 'config.php';

$categoryA = 'PlayStation';
$categoryB = 'Nintendo Switch';

function getEntryIDsByPlatform($link, $platform) {
    $stmt = $link->prepare("SELECT entry_id FROM wp_gf_entry_meta WHERE meta_key = '3' AND meta_value = ?");
    $stmt->bind_param("s", $platform);
    $stmt->execute();
    $result = $stmt->get_result();
    $ids = [];
    while ($row = $result->fetch_assoc()) {
        $ids[] = $row['entry_id'];
    }
    return $ids;
}

function countByMeta($link, $entryIDs, $metaKey) {
    if (empty($entryIDs)) return [];
    $idList = implode(',', array_map('intval', $entryIDs));
    $query = "SELECT meta_value, COUNT(*) as count
              FROM wp_gf_entry_meta
              WHERE meta_key = ? AND entry_id IN ($idList)
              GROUP BY meta_value";

    $stmt = $link->prepare($query);
    $stmt->bind_param("s", $metaKey);
    $stmt->execute();
    $result = $stmt->get_result();
    $counts = [];
    while ($row = $result->fetch_assoc()) {
        $counts[$row['meta_value']] = $row['count'];
    }
    return $counts;
}

$idsA = getEntryIDsByPlatform($link, $categoryA);
$idsB = getEntryIDsByPlatform($link, $categoryB);

$data = [
    'entries' => [
        $categoryA => count($idsA),
        $categoryB => count($idsB),
    ],
    'genres' => [
        $categoryA => countByMeta($link, $idsA, '4'),
        $categoryB => countByMeta($link, $idsB, '4'),
    ],
    'budget' => [
        $categoryA => countByMeta($link, $idsA, '5'),
        $categoryB => countByMeta($link, $idsB, '5'),
    ],
    'console' => [
        $categoryA => countByMeta($link, $idsA, '6'),
        $categoryB => countByMeta($link, $idsB, '6'),
    ],
    'game_pass' => [
        $categoryA => countByMeta($link, $idsA, '10'),  
        $categoryB => countByMeta($link, $idsB, '10'),
    ],
    'delivery' => [
        $categoryA => countByMeta($link, $idsA, '9'),
        $categoryB => countByMeta($link, $idsB, '9'),
    ],
    'franchise' => [
        $categoryA => countByMeta($link, $idsA, '8'),
        $categoryB => countByMeta($link, $idsB, '8'),
    ],
];

function renderPaginatedTab($tabId, $dataA, $dataB, $categoryA, $categoryB) {
    $all_keys = array_unique(array_merge(array_keys($dataA), array_keys($dataB)));
    sort($all_keys); // sort keys alphabetically

    // Chart containers
    echo "<div class='tab-pane fade' id='tab-$tabId' role='tabpanel' aria-labelledby='$tabId-tab'>";
    echo "<div class='row mb-4'>";
    echo "</div>"; // Close row

    // Table container
    echo "<div class='paginated-section mt-4'>";
    echo "<table class='compare-table data-table'>";
    echo "<thead><tr><th>Option</th><th>" . htmlspecialchars($categoryA) . "</th><th>" . htmlspecialchars($categoryB) . "</th></tr></thead><tbody>";

    $jsDataA = [];
    $jsDataB = [];

    foreach ($all_keys as $key) {
        $countA = $dataA[$key] ?? 0;
        $countB = $dataB[$key] ?? 0;

        // Escape for display
        $escapedKey = htmlspecialchars($key);
        echo "<tr><td>$escapedKey</td><td>$countA</td><td>$countB</td></tr>";

        // Prepare for JS chart
        $safeKey = addslashes($key);
        $jsDataA[] = "{ label: \"$safeKey\", y: $countA }";
        $jsDataB[] = "{ label: \"$safeKey\", y: $countB }";
    }

    echo "</tbody></table>";

    // Pagination controls
    echo "<div class='pagination mt-2'>";
    echo "<button class='prevBtn btn btn-sm btn-secondary'>Previous</button>";
    echo "<span class='pageInfo mx-2'></span>";
    echo "<button class='nextBtn btn btn-sm btn-secondary'>Next</button>";
    echo "</div></div></div>";
}
?>
<!DOCTYPE HTML>
<html>
<head>  
<script src="https://cdn.canvasjs.com/canvasjs.min.js"></script>
<script>
window.onload = function () {
<?php
$chartIndex = 0;
foreach ($data as $category => $platformData) {
    foreach ($platformData as $platform => $values) {
        $containerId = "chartContainer_" . $chartIndex;

        // Prepare data points
        $dataPoints = [];
        foreach ($values as $label => $count) {
            $dataPoints[] = ["label" => $label, "y" => $count];
        }

        // Output JavaScript chart creation
        echo "var chart{$chartIndex} = new CanvasJS.Chart(\"{$containerId}\", {
            animationEnabled: true,
            title: {
                text: \"" . ucfirst($category) . " - " . $platform . "\"
            },
            data: [{
                type: \"pie\",
                showInLegend: true,
                legendText: \"{label}\",
                indexLabelFontSize: 14,
                indexLabel: \"{label} - #percent%\",
                yValueFormatString: \"#,##0\",
                dataPoints: " . json_encode($dataPoints, JSON_NUMERIC_CHECK) . "
            }]
        });
        chart{$chartIndex}.render();\n";
        $chartIndex++;
    }
}
?>
};
</script>
</head>
<body>
<?php
$chartIndex = 0;
foreach ($data as $category => $platformData) {
    foreach ($platformData as $platform => $values) {
        echo "<div id='chartContainer_{$chartIndex}' style='height: 370px; width: 100%; margin-bottom: 40px;'></div>\n";
        $chartIndex++;
    }
}
?>
</body>
</html>
