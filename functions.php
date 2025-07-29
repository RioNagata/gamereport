<?php
include 'config.php';

// === INPUTS ===
$categoryA = 'PlayStation';
$categoryB = 'Nintendo Switch';

function getEntryIDsByPlatform($link, $platform) {
  $stmt = $link->prepare("SELECT entry_id FROM wp_gf_entry_meta WHERE meta_key = '3' AND meta_value = ?");
  $stmt->bind_param("s", $platform);
  $stmt->execute();
  $result = $stmt->get_result();

  $entryIDs = [];
  while ($row = $result->fetch_assoc()) {
    $entryIDs[] = (int)$row['entry_id'];
  }

  return $entryIDs;
}

function countByMeta($link, $entryIDs, $metaKey) {
  if (empty($entryIDs)) return [];
  $idList = implode(',', array_map('intval', $entryIDs));
  $query = "SELECT meta_value, COUNT(*) as count FROM wp_gf_entry_meta WHERE entry_id IN ($idList) AND meta_key = ? GROUP BY meta_value";

  $stmt = $link->prepare($query);
  $stmt->bind_param("s", $metaKey);
  $stmt->execute();
  $result = $stmt->get_result();

  $counts = [];
  while ($row = $result->fetch_assoc()) {
    $counts[$row['meta_value']] = (int)$row['count'];
  }

  return $counts;
}

$idsA = getEntryIDsByPlatform($link, $categoryA);
$idsB = getEntryIDsByPlatform($link, $categoryB);

$categories = [
  'Genres' => '4',
  'Budget' => '5',
  'Console' => '3',
  'Game Pass' => '8',
  'Delivery' => '9',
  'Franchise' => '10'
];

$results = [];
foreach ($categories as $label => $metaKey) {
  $results[$label] = [
    htmlspecialchars($categoryA) => countByMeta($link, $idsA, $metaKey),
    htmlspecialchars($categoryB) => countByMeta($link, $idsB, $metaKey),
  ];
}
?>

<!-- Include CanvasJS -->
<script src="https://cdn.canvasjs.com/canvasjs.min.js"></script>
<link rel="stylesheet" href="style.css">
<ul class="nav nav-tabs mb-3" id="statsTabs" role="tablist">
  <li class="nav-item" role="presentation">
    <button class="nav-link active" id="entries-tab"
            data-bs-toggle="tab" data-bs-target="#entries-pane"
            type="button" role="tab" aria-controls="entries-pane" aria-selected="true">
      Entries
    </button>
  </li>
  <?php foreach ($categories as $name => $metaKey): ?>
  <li class="nav-item" role="presentation">
    <button class="nav-link" id="<?= strtolower($name) ?>-tab"
            data-bs-toggle="tab" data-bs-target="#<?= strtolower($name) ?>-pane"
            type="button" role="tab" aria-controls="<?= strtolower($name) ?>-pane" aria-selected="false">
      <?= $name ?>
    </button>
  </li>
  <?php endforeach; ?>
</ul>

<div class="tab-content">
  <!-- === Entry Count Tab === -->
  <div class="tab-pane fade show active" id="entries-pane" role="tabpanel" aria-labelledby="entries-tab">
    <h3 class="text-center">Entry Count</h3>
    <table class="table table-bordered text-center">
      <thead><tr><th>Category</th><th>Entries</th></tr></thead>
      <tbody>
        <tr><td><?= htmlspecialchars($categoryA) ?></td><td><?= count($idsA) ?></td></tr>
        <tr><td><?= htmlspecialchars($categoryB) ?></td><td><?= count($idsB) ?></td></tr>
      </tbody>
    </table>
    <div id="chart-entries" class="chart"></div>
  </div>

  <!-- === Category Tabs === -->
  <?php foreach ($results as $label => $data): ?>
  <div class="tab-pane fade" id="<?= strtolower($label) ?>-pane" role="tabpanel" aria-labelledby="<?= strtolower($label) ?>-tab">
    <h3 class="text-center">Top <?= $label ?></h3>
    <table class="table table-bordered text-center">
      <thead><tr><th><?= $label ?></th><th><?= htmlspecialchars($categoryA) ?></th><th><?= htmlspecialchars($categoryB) ?></th></tr></thead>
      <tbody>
        <?php
        $keys = array_unique(array_merge(array_keys($data[$categoryA]), array_keys($data[$categoryB])));
        foreach ($keys as $key):
          $aCount = $data[$categoryA][$key] ?? 0;
          $bCount = $data[$categoryB][$key] ?? 0;
        ?>
        <tr>
          <td><?= htmlspecialchars($key) ?></td>
          <td><?= $aCount ?></td>
          <td><?= $bCount ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    <div id="chart-<?= strtolower($label) ?>" class="chart"></div>
  </div>
  <?php endforeach; ?>
</div>

<script>
function renderPieChart(containerId, title, dataPoints) {
  new CanvasJS.Chart(containerId, {
    animationEnabled: true,
    title: { text: title },
    data: [{
      type: "pie",
      indexLabel: "{label} ({y})",
      dataPoints: dataPoints
    }]
  }).render();
}

window.onload = function () {
  renderPieChart("chart-entries", "Entry Distribution", [
    { label: "<?= $categoryA ?>", y: <?= count($idsA) ?> },
    { label: "<?= $categoryB ?>", y: <?= count($idsB) ?> }
  ]);

  <?php foreach ($results as $label => $data): ?>
    renderPieChart("chart-<?= strtolower($label) ?>", "<?= $label ?> Distribution", [
      <?php
        $totalA = array_sum($data[$categoryA]);
        $totalB = array_sum($data[$categoryB]);
      ?>
      { label: "<?= $categoryA ?>", y: <?= $totalA ?> },
      { label: "<?= $categoryB ?>", y: <?= $totalB ?> }
    ]);
  <?php endforeach; ?>
}
</script>

<style>
.chart {
  height: 400px;
  width: 100%;
  margin-top: 20px;
}
</style>
