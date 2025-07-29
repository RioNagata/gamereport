<?php
include 'config.php';

// Get unique platforms from the database
$platforms = [];
$result = $link->query("SELECT DISTINCT meta_value FROM wp_gf_entry_meta WHERE meta_key = '3'");
while ($row = $result->fetch_assoc()) {
    $platforms[] = $row['meta_value'];
}

// Default or GET-selected consoles
$categoryA = $_GET['consoleA'] ?? 'PlayStation';
$categoryB = $_GET['consoleB'] ?? 'Nintendo Switch';

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

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>Comparison Report: <?= htmlspecialchars($categoryA) ?> vs <?= htmlspecialchars($categoryB) ?></title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
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
  <div class="d-flex">
<?php include 'navigation.php'; ?>

<div class="content w-100">
    <h1 class="mb-4">Comparison: <?= htmlspecialchars($categoryA) ?> vs <?= htmlspecialchars($categoryB) ?></h1>

<form method="get" class="row g-3 mb-4">
  <div class="col-auto">
    <label for="consoleA" class="form-label">Select Console A</label>
    <select name="consoleA" id="consoleA" class="form-select">
      <?php foreach ($platforms as $platform): ?>
        <option value="<?= htmlspecialchars($platform) ?>" <?= $platform === $categoryA ? 'selected' : '' ?>>
          <?= htmlspecialchars($platform) ?>
        </option>
      <?php endforeach; ?>
    </select>
  </div>

  <div class="col-auto">
    <label for="consoleB" class="form-label">Select Console B</label>
    <select name="consoleB" id="consoleB" class="form-select">
      <?php foreach ($platforms as $platform): ?>
        <option value="<?= htmlspecialchars($platform) ?>" <?= $platform === $categoryB ? 'selected' : '' ?>>
          <?= htmlspecialchars($platform) ?>
        </option>
      <?php endforeach; ?>
    </select>
  </div>

  <div class="col-auto align-self-end">
    <button type="submit" class="btn btn-primary">Compare</button>
  </div>
</form>

    <ul class="nav nav-tabs mb-3" id="statsTabs" role="tablist">
      <li class="nav-item" role="presentation">
        <button class="nav-link active" id="entry-tab"
                data-bs-toggle="tab" data-bs-target="#tab-entry"
                type="button" role="tab" aria-controls="tab-entry" aria-selected="true">
          Entry Count
        </button>
      </li>
      <li class="nav-item" role="presentation">
        <button class="nav-link" id="genres-tab"
                data-bs-toggle="tab" data-bs-target="#tab-genres"
                type="button" role="tab" aria-controls="tab-genres" aria-selected="false">
          Genre Preferences
        </button>
      </li>
      <li class="nav-item" role="presentation">
        <button class="nav-link" id="budget-tab"
                data-bs-toggle="tab" data-bs-target="#tab-budget"
                type="button" role="tab" aria-controls="tab-budget" aria-selected="false">
          Budget Range
        </button>
      </li>
      <li class="nav-item" role="presentation">
        <button class="nav-link" id="console-tab"
                data-bs-toggle="tab" data-bs-target="#tab-console"
                type="button" role="tab" aria-controls="tab-console" aria-selected="false">
          Console Included?
        </button>
      </li>
      <li class="nav-item" role="presentation">
        <button class="nav-link" id="gamepass-tab"
                data-bs-toggle="tab" data-bs-target="#tab-gamepass"
                type="button" role="tab" aria-controls="tab-gamepass" aria-selected="false">
          Game Pass Subscription
        </button>
      </li>
      <li class="nav-item" role="presentation">
        <button class="nav-link" id="delivery-tab"
                data-bs-toggle="tab" data-bs-target="#tab-delivery"
                type="button" role="tab" aria-controls="tab-delivery" aria-selected="false">
          Delivery Method
        </button>
      </li>
      <li class="nav-item" role="presentation">
        <button class="nav-link" id="franchise-tab"
                data-bs-toggle="tab" data-bs-target="#tab-franchise"
                type="button" role="tab" aria-controls="tab-franchise" aria-selected="false">
          Preferred Franchise
        </button>
      </li>
    </ul>

    <div class="tab-content">
        <?php
              $tabKeys = [
                  "genres",
                  "budget",
                  "console",
                  "game_pass",
                  "delivery",
                  "franchise"
              ];

              foreach ($tabKeys as $key) {
                  renderPaginatedTab($key, $data[$key][$categoryA], $data[$key][$categoryB], $categoryA, $categoryB);
              }
        ?>
        <div class="tab-pane fade show active" id="tab-entry" role="tabpanel" aria-labelledby="entry-tab">
            <table class="compare-table data-table">
                <thead><tr><th>Category</th><th>Count</th></tr></thead>
                <tbody>
                    <tr><td><?= htmlspecialchars($categoryA) ?></td><td><?= $data['entries'][$categoryA] ?></td></tr>
                    <tr><td><?= htmlspecialchars($categoryB) ?></td><td><?= $data['entries'][$categoryB] ?></td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>
  </div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://canvasjs.com/assets/script/canvasjs.min.js"></script>
<script>
  // Global arrays to manage charts
  const allCharts = []; // Stores actual CanvasJS chart objects
  const chartConfigurations = []; // Stores chart configurations before initial render

  function safeRender(chart) {
    if (chart && typeof chart.render === 'function') {
      chart.render();
    }
  }

  // Function to render all charts from their configurations
  function renderAllChartsFromConfig() {
    chartConfigurations.forEach(config => {
        const chart = new CanvasJS.Chart(config.containerId, config.options);
        chart.render();
        allCharts.push(chart); // Store the rendered chart object
    });
    // Clear configurations after initial render if not needed again
    chartConfigurations.length = 0;
  }

  document.querySelectorAll('.paginated-section').forEach(section => {
    const tableBody = section.querySelector('table.data-table tbody');
    const rows = Array.from(tableBody.querySelectorAll('tr'));
    const prevBtn = section.querySelector('.prevBtn');
    const nextBtn = section.querySelector('.nextBtn');
    const pageInfo = section.querySelector('.pageInfo');
    const rowsPerPage = 5;
    let currentPage = 1;
    const totalPages = Math.ceil(rows.length / rowsPerPage);

    function renderPage(page) {
      currentPage = page;
      rows.forEach(row => row.style.display = 'none');
      const start = (page - 1) * rowsPerPage;
      const end = start + rowsPerPage;
      rows.slice(start, end).forEach(row => row.style.display = '');

      pageInfo.textContent = `Page ${currentPage} of ${totalPages}`;
      prevBtn.disabled = currentPage === 1;
      nextBtn.disabled = currentPage === totalPages;
    }

    prevBtn.addEventListener('click', () => {
      if (currentPage > 1) renderPage(currentPage - 1);
    });

    nextBtn.addEventListener('click', () => {
      if (currentPage < totalPages) renderPage(currentPage + 1);
    });

    renderPage(1);
  });


  // --- Chart Initialization and Responsiveness ---

  // 1. Initial render on window load (after all assets are loaded and layout is stable)
  window.addEventListener('load', () => {
    // Small delay to ensure browser layout is truly settled
    setTimeout(() => {
        renderAllChartsFromConfig();
        // Also render charts for the initially active tab if it's not handled by renderAllChartsFromConfig
        // (The 'entry' tab does not have charts, so no action needed here for it specifically)
        // If 'genres' or another tab is initially active with charts, they will be rendered by renderAllChartsFromConfig
    }, 100); // 100ms delay
  });

  // 2. Re-render all charts on window resize
  window.addEventListener('resize', () => {
    allCharts.forEach(safeRender);
  });

  // 3. Re-render charts when their specific Bootstrap tab is shown
  // This is crucial for charts in initially hidden tabs (all tabs except the active one)
  const tabs = document.querySelectorAll('button[data-bs-toggle="tab"]');
  tabs.forEach(tabButton => {
      tabButton.addEventListener('shown.bs.tab', function (event) {
          const targetTabId = event.target.dataset.bsTarget; // e.g., #tab-genres
          const tabPane = document.querySelector(targetTabId);

          // Find all chart containers within the newly shown tab pane
          const chartsInTab = allCharts.filter(chart =>
              tabPane.contains(document.getElementById(chart.canvas.id))
          );

          chartsInTab.forEach(safeRender);
      });
  });

</script>

</body>
</html>