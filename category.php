<?php
session_start();
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: login.php");
    exit;
}

include 'config.php';
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (isset($_GET['item']) && $_GET['item'] !== '') {
    $item = $_GET['item'];
} else {
    die("No item specified.");
}

// Last 12 months (chronological)
$pastMonths = [];
$date = new DateTime('first day of this month');
for ($i = 0; $i < 12; $i++) {
    $pastMonths[] = $date->format('Y-m');
    $date->modify('-1 month');
}
$last12Months = array_reverse($pastMonths);

// Fetch monthly counts
$categoryResultArray = [];
$chartData = []; // chronological data for chart

$stmt = $link->prepare("
    SELECT COUNT(*) AS cnt
    FROM wp_gf_entry_meta em
    INNER JOIN wp_gf_entry e ON em.entry_id = e.id
    WHERE em.meta_value = ? 
      AND e.date_created LIKE ?
");

foreach ($last12Months as $month) {
    $monthLike = $month . '%';
    $stmt->bind_param("ss", $item, $monthLike);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();

    $chartData[] = [
        "y" => (int)$row['cnt'],
        "label" => $month
    ];

    $categoryResultArray[] = [
        "y" => (int)$row['cnt'],
        "label" => $month
    ];
}

// Sort table data by most entries
usort($categoryResultArray, function($a, $b){
    return $b['y'] <=> $a['y'];
});

// Total entries
$allStmt = $link->prepare("SELECT COUNT(*) AS total FROM wp_gf_entry_meta WHERE meta_value = ?");
$allStmt->bind_param("s", $item);
$allStmt->execute();
$allResult = $allStmt->get_result()->fetch_assoc();
$allresultcount = (int)$allResult['total'];

$stmt->close();
$allStmt->close();
$link->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>GameInfo - <?php echo htmlspecialchars($item); ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/boxicons@2.0.9/css/boxicons.min.css" rel="stylesheet">
<link rel="stylesheet" href="style.css">
<script src="https://cdn.canvasjs.com/canvasjs.min.js"></script>
</head>
<body>
<div class="d-flex">
  <?php include 'navigation.php' ?>
  <div class="content w-100">
    <h3><?php echo htmlspecialchars($item); ?></h3>

    <div class="card-box mb-4 d-flex gap-3">
      <div class="card bg-light p-3">
        <div>All Entries</div>
        <h4><?php echo $allresultcount; ?></h4>
      </div>
      <div class="card bg-light p-3">
        <div>Most Popular Month</div>
        <?php
          $maxIndex = array_search(max(array_column($chartData,'y')), array_column($chartData,'y'));
          $maxMonth = $chartData[$maxIndex];
        ?>
        <h5><?php echo $maxMonth['y']; ?></h5>
        <p><?php echo $maxMonth['label']; ?></p>
      </div>
    </div>

    <!-- Category Tab -->
    <ul class="nav nav-tabs mb-3" id="statsTabs" role="tablist">
      <li class="nav-item" role="presentation">
        <button class="nav-link active" id="category-tab"
                data-bs-toggle="tab" data-bs-target="#category-pane"
                type="button" role="tab" aria-controls="category-pane" aria-selected="true">
          Category
        </button>
      </li>
    </ul>

    <div class="tab-content">
      <div class="tab-pane fade show active" id="category-pane" role="tabpanel" aria-labelledby="category-tab">
        <div class="table-chart-wrapper mb-4">
          <!-- Table -->
          <div class="paginated-section" id="category-section">
            <!-- Table Title -->
            <h4 class="text-center mb-3">Most Entries by Month</h4>

            <table class="data-table table table-striped">
                <thead>
                    <tr>
                        <th>Month</th>
                        <th>Count</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>

            <div class="pagination mt-2">
                <button class="prevBtn btn btn-sm btn-secondary">Previous</button>
                <span class="pageInfo mx-2"></span>
                <button class="nextBtn btn btn-sm btn-secondary">Next</button>
            </div>
        </div>

          <!-- Chart -->
          <div id="chartcategory" style="height: 400px;"></div>
        </div>
      </div>
    </div>

  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
const categoryTableData = <?php echo json_encode($categoryResultArray, JSON_NUMERIC_CHECK); ?>;
const categoryChartData = <?php echo json_encode($chartData, JSON_NUMERIC_CHECK); ?>;

/* ---------- CanvasJS Line Chart ---------- */
const categoryChart = new CanvasJS.Chart("chartcategory", {
    animationEnabled: true,
    theme: "light2",
    title: { text: "Monthly Entry Count" },
    axisX: { title: "Month" },
    axisY: { title: "Entries", includeZero: true },
    data: [{
        type: "line",
        markerSize: 8,
        lineColor: "#4e73df",
        markerColor: "#4e73df",
        dataPoints: categoryChartData
    }]
});
categoryChart.render();

/* ---------- Paginated Table ---------- */
function initPaginatedTable(sectionId, data, rowsPerPage = 5) {
    const section = document.getElementById(sectionId);
    if (!section) return;

    let page = 1;
    const tbody = section.querySelector('tbody');
    const prevBtn = section.querySelector('.prevBtn');
    const nextBtn = section.querySelector('.nextBtn');
    const pageInfo = section.querySelector('.pageInfo');

    const render = () => {
        const start = (page - 1) * rowsPerPage;
        const slice = data.slice(start, start + rowsPerPage);
        tbody.innerHTML = slice.map(r =>
            `<tr${r.y === Math.max(...data.map(d=>d.y)) ? ' style="background-color:#ffe082;font-weight:bold;"' : ''}>
                <td>${r.label}</td>
                <td>${r.y}</td>
            </tr>`).join('');
        const pages = Math.max(1, Math.ceil(data.length / rowsPerPage));
        pageInfo.textContent = `Page ${page} of ${pages}`;
        prevBtn.disabled = page === 1;
        nextBtn.disabled = page === pages;
    };

    prevBtn.onclick = () => { if(page>1) page--; render(); };
    nextBtn.onclick = () => { if(page<Math.ceil(data.length/rowsPerPage)) page++; render(); };
    render();
}

initPaginatedTable('category-section', categoryTableData);
</script>

</body>
</html>
