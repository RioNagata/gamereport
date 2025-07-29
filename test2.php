<?php
include 'config.php';

$categories = ['PlayStation', 'Nintendo Switch'];

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

$metaKeys = [
    'genres' => '4',
    'budget' => '5',
    'console' => '6',
    'game_pass' => '10',
    'delivery' => '9',
    'franchise' => '8',
];

$data = [];
foreach ($categories as $category) {
    $ids = getEntryIDsByPlatform($link, $category);
    foreach ($metaKeys as $key => $metaKey) {
        $data[$key][$category] = countByMeta($link, $ids, $metaKey);
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Game Comparison</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.canvasjs.com/canvasjs.min.js"></script>
</head>
<body class="p-4">

<!-- Dropdowns -->
<div class="row mb-3">
    <div class="col-md-4">
        <label class="form-label">Select Console:</label>
        <select id="consoleSelect" class="form-select">
            <option value="PlayStation">PlayStation</option>
            <option value="Nintendo Switch">Nintendo Switch</option>
        </select>
    </div>
    <div class="col-md-4">
        <label class="form-label">Select Data:</label>
        <select id="dataSelect" class="form-select">
            <?php foreach (array_keys($metaKeys) as $key): ?>
                <option value="<?= $key ?>"><?= ucfirst(str_replace('_', ' ', $key)) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
</div>

<!-- Chart and Table Container -->
<div id="outputArea">
    <div id="chartContainer" style="height: 360px; width: 100%;"></div>
    <table class="table mt-4" id="resultTable">
        <thead>
            <tr><th>Option</th><th>Count</th></tr>
        </thead>
        <tbody></tbody>
    </table>
</div>

<!-- Data Injection -->
<script>
const chartData = <?= json_encode($data) ?>;
</script>

<script>
document.addEventListener("DOMContentLoaded", function() {
    const chartContainer = document.getElementById("chartContainer");
    const tableBody = document.querySelector("#resultTable tbody");
    const consoleSelect = document.getElementById("consoleSelect");
    const dataSelect = document.getElementById("dataSelect");

    function render() {
        const console = consoleSelect.value;
        const selectedData = dataSelect.value;
        const entries = chartData[selectedData][console];

        const dataPoints = [];
        let tableRows = "";

        for (const [label, count] of Object.entries(entries)) {
            dataPoints.push({ label, y: count });
            tableRows += `<tr><td>${label}</td><td>${count}</td></tr>`;
        }

        const chart = new CanvasJS.Chart("chartContainer", {
            animationEnabled: true,
            title: { text: `${console} - ${selectedData.replace('_', ' ')}` },
            data: [{
                type: "pie",
                showInLegend: true,
                legendText: "{label}",
                indexLabel: "{label} - #percent%",
                yValueFormatString: "#,##0",
                dataPoints: dataPoints
            }]
        });
        chart.render();

        tableBody.innerHTML = tableRows;
    }

    consoleSelect.addEventListener("change", render);
    dataSelect.addEventListener("change", render);

    render(); // Initial
});
</script>

</body>
</html>
