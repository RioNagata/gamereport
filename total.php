<?php 
session_start();
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: login.php");
    exit;
}
include 'config.php';

$gameGenre = mysqli_query($link, "
  SELECT TRIM(BOTH '[]' FROM TRIM(meta_value)) AS genre, COUNT(*) AS count
  FROM wp_gf_entry_meta
  WHERE meta_key = '4'
  GROUP BY genre
  ORDER BY count DESC
");
$gameConsole = mysqli_query($link, "SELECT meta_value FROM `wp_gf_entry_meta` WHERE `meta_key` = '3' GROUP By meta_value");
$game = mysqli_query($link, "SELECT meta_value FROM `wp_gf_entry_meta` WHERE `meta_key` = '8' GROUP By meta_value");

$genrearray = array(); // Make sure to initialize the array
while ($genre = mysqli_fetch_array($gameGenre, MYSQLI_NUM)) {
    $genrearray[] = $genre[0];
}

$consolearray = array(); // Make sure to initialize the array
while ($console = mysqli_fetch_array($gameConsole, MYSQLI_NUM)) {
    $consolearray[] = $console[0];
}

$gamearray = array(); // Make sure to initialize the array
while ($games = mysqli_fetch_array($game, MYSQLI_NUM)) {
    $gamearray[] = $games[0];
}


$allcount = $link->prepare("SELECT * FROM wp_gf_entry where status = 'active'");
$allcount->execute();
$allresult = $allcount->get_result();
$allresultcount = mysqli_num_rows($allresult);

$pscount = $link->prepare("SELECT * FROM wp_gf_entry_meta WHERE meta_value = 'Switch'");
$pscount->execute();
$psresult = $pscount->get_result();
$psresultcount = mysqli_num_rows($psresult);

foreach($sevenDays as $dates){
    $dailycount = $link->prepare("SELECT * FROM wp_gf_entry WHERE date_created Like '%$dates%'");
    $dailycount->execute();
    $dailyresult = $dailycount->get_result();
    $dailyresultcount = mysqli_num_rows($dailyresult);
    $sevenDaysPoint[] = array("y" => $dailyresultcount, "label" => $dates);
}

// --- Genre Total Count ---
$genreResultArray = [];
// $genrearray was populated earlier with the unique genre strings from the initial query.
// Now, loop through each unique genre to get its ALL-TIME total count.

foreach ($genrearray as $data) {
  // Use a prepared statement to get the total count for this genre
  $stmt = $link->prepare("
    SELECT COUNT(*) as count
    FROM wp_gf_entry_meta
    WHERE TRIM(BOTH '[]' FROM TRIM(meta_value)) = ? 
      AND meta_key = '4'
  ");
  
  // Note: The value in $data already has the '[]' trimmed and spaces trimmed, 
  // so we match it against the normalized column content.
  $stmt->bind_param("s", $data);
  $stmt->execute();
  $result = $stmt->get_result();
  $row = $result->fetch_assoc();
  $count = $row['count'] ?? 0;

  // Add the genre and its TOTAL count to the array.
  $genreResultArray[] = [
    "y" => $count,
    "label" => $data
  ];
}

// Sort the final array by count (y) in descending order.
usort($genreResultArray, function($a, $b) {
  return $b['y'] <=> $a['y'];
});

// Remove entries where the count is 0 (optional, but keeps the list clean)
$filtered_genre = array_filter($genreResultArray, function($item) {
  return $item['y'] > 0;
});
$genreResultArray = array_values($filtered_genre);

// $genreResultArray is now correct and ready for display

// --- Console Count ---
$consoleResultArray = [];

foreach ($consolearray as $data) {
  $stmt = $link->prepare("
    SELECT COUNT(*) as count
    FROM wp_gf_entry_meta em
    INNER JOIN wp_gf_entry e ON em.entry_id = e.id
    WHERE em.meta_value = ?
  ");
  $stmt->bind_param("s", $data);
  $stmt->execute();
  $result = $stmt->get_result();
  $row = $result->fetch_assoc();
  $count = $row['count'] ?? 0;

  $consoleResultArray[] = [
    "y" => $count,
    "label" => $data
  ];
}

usort($consoleResultArray, function($a, $b) {
  return $b['y'] <=> $a['y'];
});


// --- Game Count ---
$gameResultArray = [];

foreach ($gamearray as $data) {
  $stmt = $link->prepare("
    SELECT COUNT(*) as count
    FROM wp_gf_entry_meta em
    INNER JOIN wp_gf_entry e ON em.entry_id = e.id
    WHERE em.meta_value = ?
  ");
  $stmt->bind_param("s", $data);
  $stmt->execute();
  $result = $stmt->get_result();
  $row = $result->fetch_assoc();
  $count = $row['count'] ?? 0;

  $gameResultArray[] = [
    "y" => $count,
    "label" => $data
  ];
}

usort($gameResultArray, function($a, $b) {
  return $b['y'] <=> $a['y'];
});
$filtered = array_filter($gameResultArray, function($item) {
  return $item['y'] > 0;
});

$gameResultArray = array_values($filtered);



mysqli_close($link);
?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>GameInfo</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/boxicons@2.0.9/css/boxicons.min.css" rel="stylesheet">
  <link rel="stylesheet" href="style.css">
  <style>
.chart {
  width: 100%;
  min-height: 500px; /* or 600px for bigger */
}
</style>

</head>

<body>
  <div class="d-flex">
    <?php include 'navigation.php'?>
    <div class="content w-100">
      <h3>Dashboard</h3>
      <div class="card-box mb-4">
        <div class="card bg-light">All Entries<h4><?php echo $allresultcount;?></h4></div>
        <div class="card bg-light">Popular Genre<h5><?php echo $genreResultArray[0]["y"]?></h5><p><?php echo $genreResultArray[0]["label"]?></p></div>
        <div class="card bg-light">Popular Console<h5><?php echo $consoleResultArray[0]["y"]?></h5><p><?php echo $consoleResultArray[0]["label"]?></p></div>
        <div class="card bg-light">Popular Game<h5><?php echo $gameResultArray[0]["y"]?></h5><p><?php echo $gameResultArray[0]["label"]?></p></div>
      </div>
      <!-- ==========  NAV‑TABS  ========== -->
<ul class="nav nav-tabs mb-3" id="statsTabs" role="tablist">
  <li class="nav-item" role="presentation">
    <button class="nav-link active" id="genre-tab"
            data-bs-toggle="tab" data-bs-target="#genre-pane"
            type="button" role="tab" aria-controls="genre-pane" aria-selected="true">
      Genres
    </button>
  </li>
  <li class="nav-item" role="presentation">
    <button class="nav-link" id="console-tab"
            data-bs-toggle="tab" data-bs-target="#console-pane"
            type="button" role="tab" aria-controls="console-pane" aria-selected="false">
      Consoles
    </button>
  </li>
  <li class="nav-item" role="presentation">
    <button class="nav-link" id="game-tab"
            data-bs-toggle="tab" data-bs-target="#game-pane"
            type="button" role="tab" aria-controls="game-pane" aria-selected="false">
      Games
    </button>
  </li>
</ul>

<!-- ==========  TAB‑PANES  ========== -->
<div class="tab-content">

  <!-- ===== Genres ===== -->
  <div class="tab-pane fade show active" id="genre-pane" role="tabpanel" aria-labelledby="genre-tab">
  <h1 class="table-title text-center">Top Genres</h1>
  <div class="table-chart-wrapper mb-4">
    <!-- Table Section -->
    <div class="paginated-section" id="genre-section">
      <table class="data-table">
        <thead><tr><th>Name</th><th>Count</th></tr></thead>
        <tbody></tbody>
      </table>
      <div class="pagination">
        <button class="prevBtn btn btn-sm btn-secondary">Previous</button>
        <span class="pageInfo mx-2"></span>
        <button class="nextBtn btn btn-sm btn-secondary">Next</button>
      </div>
    </div>
    <!-- Chart Section -->
    <div>
      <div id="chartGenres" class="chart"></div>
    </div>
  </div>
</div>

  <!-- ===== Consoles ===== -->
  <div class="tab-pane fade" id="console-pane" role="tabpanel" aria-labelledby="console-tab">
  <h1 class="table-title text-center">Top Consoles</h1>
  <div class="table-chart-wrapper mb-4">
    <div>
      <div class="paginated-section" id="console-section">
        <table class="data-table">
          <thead><tr><th>Name</th><th>Count</th></tr></thead>
          <tbody></tbody>
        </table>
        <div class="pagination">
          <button class="prevBtn btn btn-sm btn-secondary">Previous</button>
          <span class="pageInfo mx-2"></span>
          <button class="nextBtn btn btn-sm btn-secondary">Next</button>
        </div>
      </div>
    </div>
    <div>
      <div id="chartConsoles" class="chart"></div>
    </div>
  </div>
</div>

  <!-- ===== Games ===== -->
  <div class="tab-pane fade" id="game-pane" role="tabpanel" aria-labelledby="game-tab">
    <h1 class="table-title text-center">Top Games</h1>
    <div class="table-chart-wrapper mb-4">
      <div class="paginated-section" id="game-section">
        <table class="data-table">
          <thead><tr><th>Name</th><th>Count</th></tr></thead>
          <tbody></tbody>
        </table>
        <div class="pagination">
          <button class="prevBtn btn btn-sm btn-secondary">Previous</button>
          <span class="pageInfo mx-2"></span>
          <button class="nextBtn btn btn-sm btn-secondary">Next</button>
        </div>
      </div>
      <div>
        <div id="chartGames" class="chart"></div>
      </div>
    </div>
  </div>

</div><!-- /.tab-content -->


<!-- ==========  SCRIPTS (Bootstrap + CanvasJS)  ========== -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.canvasjs.com/canvasjs.min.js"></script>
<!-- put this right after you include CanvasJS and BEFORE you call initPaginatedTable -->
<script>
  /* >>> JavaScript versions of the PHP arrays <<< */
  const genreData   = <?php echo json_encode($genreResultArray,   JSON_NUMERIC_CHECK); ?>;
  const consoleData = <?php echo json_encode($consoleResultArray, JSON_NUMERIC_CHECK); ?>;
  const gameData    = <?php echo json_encode($gameResultArray,    JSON_NUMERIC_CHECK); ?>;
</script>
<script>
function makeChart(container, title, data) {
  const chart = new CanvasJS.Chart(container, {
    animationEnabled: true,
    theme: "light2",
    title:{ text: title },
    axisY:{ title: "Entries" },
    axisX:{ labelAngle: -45 },
    legend:{ cursor: "pointer" },
    data: [
      { type: "column", name: month1, showInLegend: true, dataPoints: data.map(r => ({ label: r.label, y: r.y1 })) },
      { type: "column", name: month2, showInLegend: true, dataPoints: data.map(r => ({ label: r.label, y: r.y2 })) }
    ]
  });
  return chart; // Don't render yet
}



/* ---------- paginator helper ---------- */
function initPaginatedTable (sectionId, data, rowsPerPage = 5) {
  const section = document.getElementById(sectionId);
  if (!section) return;
  let page = 1;
  const tbody    = section.querySelector('tbody');
  const prevBtn  = section.querySelector('.prevBtn');
  const nextBtn  = section.querySelector('.nextBtn');
  const pageInfo = section.querySelector('.pageInfo');

  const render = () => {
    const start = (page - 1) * rowsPerPage;
    const slice = data.slice(start, start + rowsPerPage);
    tbody.innerHTML = slice.map(r => `
  <tr>
    <td><a href="category.php?item=${encodeURIComponent(r.label)}" class="text-decoration-none">${r.label}</a></td>
    <td>${r.y}</td>
  </tr>
`).join('');

    const pages = Math.max(1, Math.ceil(data.length / rowsPerPage));
    pageInfo.textContent = `Page ${page} of ${pages}`;
    prevBtn.disabled = page === 1;
    nextBtn.disabled = page === pages;
  };
  prevBtn.onclick = () => { if (page > 1) page--; render(); };
  nextBtn.onclick = () => { if (page < Math.ceil(data.length / rowsPerPage)) page++; render(); };
  render();
}

/* helper renders twice so width is 100 % after flex layout */
const safeRender = c => { c.render(); requestAnimationFrame(() => c.render()); };

/* first visible tab */
safeRender(genreChart);

/* re‑render when a tab becomes visible */
document.getElementById('statsTabs')
        .addEventListener('shown.bs.tab', e => {
  if (e.target.id === 'console-tab') safeRender(consoleChart);
  if (e.target.id === 'game-tab')    safeRender(gameChart);
  if (e.target.id === 'genre-tab')   safeRender(genreChart);
  window.dispatchEvent(new Event('resize'));   // ensure final resize pass
});

/* keep charts responsive on window resize */
window.addEventListener('resize', () => {
  [genreChart, consoleChart, gameChart].forEach(safeRender);
});
</script>
<!-- ↓ your paginator + chart code here ↓ -->
<script>
  /* data arrays already emitted by PHP above */
  initPaginatedTable('genre-section',   genreData);
  initPaginatedTable('console-section', consoleData);
  initPaginatedTable('game-section',    gameData);

  /* build charts … safeRender … tab listener … (full block we sent) */
</script>
<script>
const genreChart   = makeChart("chartGenres",   "Genres Comparison",   genreData);
const consoleChart = makeChart("chartConsoles", "Consoles Comparison", consoleData);
const gameChart    = makeChart("chartGames",    "Games Comparison",    gameData);

// Render the first (visible) one immediately
genreChart.render();

// When a tab becomes visible, re-render its chart
document.getElementById('statsTabs').addEventListener('shown.bs.tab', e => {
  setTimeout(() => {
    if (e.target.id === 'console-tab') consoleChart.render();
    if (e.target.id === 'game-tab')    gameChart.render();
    if (e.target.id === 'genre-tab')   genreChart.render();
  }, 200); // delay ensures Bootstrap finishes tab animation
});

// Also handle window resizing
window.addEventListener('resize', () => {
  [genreChart, consoleChart, gameChart].forEach(c => c.render());
});

</script>



</body>

</html>