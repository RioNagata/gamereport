<?php
session_start();
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: login.php");
    exit;
}
include 'config.php';

// === 1. Get all available months from database ===
$monthsQuery = "SELECT DISTINCT DATE_FORMAT(date_created, '%Y-%m') AS month FROM wp_gf_entry ORDER BY month DESC";
$monthsResult = mysqli_query($link, $monthsQuery);
$availableMonths = [];
while ($row = mysqli_fetch_assoc($monthsResult)) {
    $availableMonths[] = $row['month'];
}

// === 2. Get user-selected months (fallback to latest 2) ===
$month1 = $_GET['month1'] ?? ($availableMonths[1] ?? '');
$month2 = $_GET['month2'] ?? ($availableMonths[0] ?? '');

// === 3. Helper function to count items per category per month ===
function getCountsByMetaKeyAndMonth($link, $meta_key, $month) {
    $sql = "SELECT m.meta_value, COUNT(*) as cnt
            FROM wp_gf_entry_meta m
            JOIN wp_gf_entry e ON m.entry_id = e.id
            WHERE m.meta_key = ?
              AND e.status = 'active'
              AND e.date_created LIKE ?
            GROUP BY m.meta_value";
    $stmt = $link->prepare($sql);
    $like = $month . '%';
    $stmt->bind_param("ss", $meta_key, $like);
    $stmt->execute();
    $result = $stmt->get_result();
    $counts = [];
    while ($row = $result->fetch_assoc()) {
        $counts[$row['meta_value']] = (int)$row['cnt'];
    }
    return $counts;
}

// === 4. Get counts for both months for each category ===
$genre_m1   = getCountsByMetaKeyAndMonth($link, '4', $month1);
$genre_m2   = getCountsByMetaKeyAndMonth($link, '4', $month2);
$console_m1 = getCountsByMetaKeyAndMonth($link, '3', $month1);
$console_m2 = getCountsByMetaKeyAndMonth($link, '3', $month2);
$game_m1    = getCountsByMetaKeyAndMonth($link, '8', $month1);
$game_m2    = getCountsByMetaKeyAndMonth($link, '8', $month2);

// === 5. Merge + calculate % change ===
function compareData($arr1, $arr2) {
    $allKeys = array_unique(array_merge(array_keys($arr1), array_keys($arr2)));
    $data = [];
    foreach ($allKeys as $key) {
        $y1 = $arr1[$key] ?? 0;
        $y2 = $arr2[$key] ?? 0;
        $percent = $y1 > 0 ? (($y2 - $y1) / $y1) * 100 : ($y2 > 0 ? 100 : 0);
        $data[] = [
            "label" => $key,
            "y1" => $y1,
            "y2" => $y2,
            "percent" => round($percent, 1)
        ];
    }
    return $data;
}

$genreCompare   = compareData($genre_m1, $genre_m2);
$consoleCompare = compareData($console_m1, $console_m2);
$gameCompare    = compareData($game_m1, $game_m2);

mysqli_close($link);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Compare Months</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="style.css">
  <style>
.table-wrapper {
  background: #ffffff;
  border-radius: 12px;
  box-shadow: 0 2px 10px rgba(0,0,0,0.08);
  padding: 1rem 1.5rem;
  margin-top: 1.5rem;
}

.table {
  margin-bottom: 0;
  border-collapse: separate;
  border-spacing: 0 0.5rem;
}

.table thead th {
  background-color: #f8f9fa;
  color: #495057;
  font-weight: 600;
  text-align: center;
  border: none;
}

.table tbody tr {
  background: #fff;
  transition: all 0.2s ease;
}

.table tbody tr:hover {
  background: #f1f5fb;
  transform: scale(1.01);
}

.table td {
  vertical-align: middle;
  border-top: none;
}

.table td:first-child {
  font-weight: 500;
  color: #333;
}

.table td.text-success {
  color: #28a745 !important;
  font-weight: 600;
}

.table td.text-danger {
  color: #dc3545 !important;
  font-weight: 600;
}

/* Make column widths consistent */
.table th, .table td {
  text-align: center;
}

@media (max-width: 768px) {
  .table-wrapper {
    padding: 0.75rem;
  }
}
</style>

</head>
<body>
<div class="d-flex">
  <?php include 'navigation.php'; ?>
  <div class="content w-100">
    <h3>Compare Months</h3>

    <!-- Month Selectors -->
    <form method="get" class="mb-4 d-flex gap-3 flex-wrap">
      <div>
        <label>Month 1</label>
        <select name="month1" class="form-select">
          <?php foreach ($availableMonths as $m): ?>
            <option value="<?php echo $m; ?>" <?php echo $m == $month1 ? 'selected' : ''; ?>><?php echo $m; ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div>
        <label>Month 2</label>
        <select name="month2" class="form-select">
          <?php foreach ($availableMonths as $m): ?>
            <option value="<?php echo $m; ?>" <?php echo $m == $month2 ? 'selected' : ''; ?>><?php echo $m; ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="align-self-end">
        <button class="btn btn-primary">Compare</button>
      </div>
    </form>

    <!-- Tabs -->
    <ul class="nav nav-tabs" id="compareTabs" role="tablist">
      <li class="nav-item" role="presentation">
        <button class="nav-link active" id="genres-tab" data-bs-toggle="tab" data-bs-target="#genres" type="button" role="tab">🎵 Genres</button>
      </li>
      <li class="nav-item" role="presentation">
        <button class="nav-link" id="consoles-tab" data-bs-toggle="tab" data-bs-target="#consoles" type="button" role="tab">🎮 Consoles</button>
      </li>
      <li class="nav-item" role="presentation">
        <button class="nav-link" id="games-tab" data-bs-toggle="tab" data-bs-target="#games" type="button" role="tab">🕹️ Games</button>
      </li>
    </ul>

    <div class="tab-content mt-4" id="compareTabsContent">
      <!-- ===== Genres ===== -->
      <div class="tab-pane fade show active" id="genres" role="tabpanel">
        <div id="chartGenres" style="height: 400px; width: 100%;"></div>
        <div class="table-wrapper">
        <table class="table align-middle">

          <thead><tr><th>Genre</th><th><?php echo $month1; ?></th><th><?php echo $month2; ?></th><th>% Change</th></tr></thead>
          <tbody>
            <?php foreach ($genreCompare as $r): ?>
              <tr>
                <td><?php echo htmlspecialchars($r['label']); ?></td>
                <td><?php echo $r['y1']; ?></td>
                <td><?php echo $r['y2']; ?></td>
                <td class="<?php echo $r['percent'] >= 0 ? 'text-success' : 'text-danger'; ?>">
                  <?php echo $r['percent'] . '%'; ?>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
        </div>
      </div>

      <!-- ===== Consoles ===== -->
      <div class="tab-pane fade" id="consoles" role="tabpanel">
        <div id="chartConsoles" style="height: 400px; width: 100%;"></div>
        <div class="table-wrapper">
  <table class="table align-middle">

          <thead><tr><th>Console</th><th><?php echo $month1; ?></th><th><?php echo $month2; ?></th><th>% Change</th></tr></thead>
          <tbody>
            <?php foreach ($consoleCompare as $r): ?>
              <tr>
                <td><?php echo htmlspecialchars($r['label']); ?></td>
                <td><?php echo $r['y1']; ?></td>
                <td><?php echo $r['y2']; ?></td>
                <td class="<?php echo $r['percent'] >= 0 ? 'text-success' : 'text-danger'; ?>">
                  <?php echo $r['percent'] . '%'; ?>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
        </div>
      </div>

      <!-- ===== Games ===== -->
      <div class="tab-pane fade" id="games" role="tabpanel">
        <div id="chartGames" style="height: 400px; width: 100%;"></div>
        <div class="table-wrapper">
           <table class="table align-middle">
          <thead><tr><th>Game</th><th><?php echo $month1; ?></th><th><?php echo $month2; ?></th><th>% Change</th></tr></thead>
          <tbody>
            <?php foreach ($gameCompare as $r): ?>
              <tr>
                <td><?php echo htmlspecialchars($r['label']); ?></td>
                <td><?php echo $r['y1']; ?></td>
                <td><?php echo $r['y2']; ?></td>
                <td class="<?php echo $r['percent'] >= 0 ? 'text-success' : 'text-danger'; ?>">
                  <?php echo $r['percent'] . '%'; ?>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
        </div>
      </div>
    </div>
  </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.canvasjs.com/canvasjs.min.js"></script>

<script>
// PHP → JS data
const month1 = "<?php echo $month1; ?>";
const month2 = "<?php echo $month2; ?>";
const genreData   = <?php echo json_encode($genreCompare,   JSON_NUMERIC_CHECK); ?>;
const consoleData = <?php echo json_encode($consoleCompare, JSON_NUMERIC_CHECK); ?>;
const gameData    = <?php echo json_encode($gameCompare,    JSON_NUMERIC_CHECK); ?>;

// ========== Chart Builder Function ==========
function makeChart(container, title, data) {
  return new CanvasJS.Chart(container, {
    animationEnabled: true,
    theme: "light2",
    title: { text: title },
    axisY: { title: "Entries" },
    axisX: { labelAngle: -45 },
    toolTip: { shared: true },
    legend: { cursor: "pointer" },
    data: [
      { 
        type: "column",
        name: month1,
        showInLegend: true,
        color: "#4e73df",
        dataPoints: data.map(r => ({ label: r.label, y: r.y1 }))
      },
      { 
        type: "column",
        name: month2,
        showInLegend: true,
        color: "#1cc88a",
        dataPoints: data.map(r => ({ label: r.label, y: r.y2 }))
      }
    ]
  });
}

// ========== Initialize Charts ==========
document.addEventListener("DOMContentLoaded", () => {
  // Create all charts
  const genreChart   = makeChart("chartGenres",   "Genre Comparison",   genreData);
  const consoleChart = makeChart("chartConsoles", "Console Comparison", consoleData);
  const gameChart    = makeChart("chartGames",    "Game Comparison",    gameData);

  // Helper to render twice to fix width issues on hidden tabs
  const safeRender = c => { c.render(); requestAnimationFrame(() => c.render()); };

  // Render the first visible chart (Genres)
  safeRender(genreChart);

  // Listen for Bootstrap tab switch
  document.getElementById('compareTabs').addEventListener('shown.bs.tab', e => {
    setTimeout(() => {
      if (e.target.id === 'consoles-tab') safeRender(consoleChart);
      if (e.target.id === 'games-tab')    safeRender(gameChart);
      if (e.target.id === 'genres-tab')   safeRender(genreChart);
    }, 150);
  });

  // Handle window resizing (keep charts responsive)
  window.addEventListener('resize', () => {
    [genreChart, consoleChart, gameChart].forEach(safeRender);
  });
});
</script>

</body>

</html>
