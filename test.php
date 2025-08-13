<?php

?>




<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>GameInfo</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="style.css">
  <style>
    /* Ensure flexbox for table-chart-wrapper */
    .table-chart-wrapper {
      display: flex;
      flex-direction: row;
      gap: 20px;
      align-items: stretch;
      flex-wrap: wrap;
    }
    .table-chart-wrapper > div {
      flex: 1 1 0;
      min-width: 0;
      max-width: 50%;
      display: flex;
      flex-direction: column;
    }
    @media (max-width: 992px) {
      .table-chart-wrapper { flex-direction: column; }
      .table-chart-wrapper > div { max-width: 100%; }
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
      <div class="tab-content">
        <!-- ===== Genres ===== -->
        <div class="tab-pane fade show active" id="genre-pane" role="tabpanel" aria-labelledby="genre-tab">
          <h1 class="table-title text-center">Top Genres</h1>
          <div class="table-chart-wrapper mb-4">
            <div>
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
            </div>
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
            <div>
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
            </div>
            <div>
              <div id="chartGames" class="chart"></div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
  <!-- SCRIPTS -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://cdn.canvasjs.com/canvasjs.min.js"></script>
  <script>
    const genreData   = <?php echo json_encode($genreResultArray,   JSON_NUMERIC_CHECK); ?>;
    const consoleData = <?php echo json_encode($consoleResultArray, JSON_NUMERIC_CHECK); ?>;
    const gameData    = <?php echo json_encode($gameResultArray,    JSON_NUMERIC_CHECK); ?>;
  </script>
  <script>
    const genreChart   = new CanvasJS.Chart("chartGenres", {
      animationEnabled: true, theme: "light2",
      title:{ text:"Top Genres" },
      data :[{ type:"column", dataPoints: genreData }]
    });
    const consoleChart = new CanvasJS.Chart("chartConsoles", {
      animationEnabled: true, theme: "light2",
      title:{ text:"Top Consoles" },
      data :[{ type:"column", dataPoints: consoleData }]
    });
    const gameChart    = new CanvasJS.Chart("chartGames", {
      animationEnabled: true, theme: "light2",
      title:{ text:"Top Games" },
      data :[{ type:"column", dataPoints: gameData }]
    });

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
        tbody.innerHTML = slice.map(r => `<tr><td>${r.label}</td><td>${r.y}</td></tr>`).join('');
        const pages = Math.max(1, Math.ceil(data.length / rowsPerPage));
        pageInfo.textContent = `Page ${page} of ${pages}`;
        prevBtn.disabled = page === 1;
        nextBtn.disabled = page === pages;
      };
      prevBtn.onclick = () => { if (page > 1) page--; render(); };
      nextBtn.onclick = () => { if (page < Math.ceil(data.length / rowsPerPage)) page++; render(); };
      render();
    }
    const safeRender = c => { c.render(); requestAnimationFrame(() => c.render()); };
    safeRender(genreChart);
    document.getElementById('statsTabs')
            .addEventListener('shown.bs.tab', e => {
      if (e.target.id === 'console-tab') safeRender(consoleChart);
      if (e.target.id === 'game-tab')    safeRender(gameChart);
      if (e.target.id === 'genre-tab')   safeRender(genreChart);
      window.dispatchEvent(new Event('resize'));
    });
    window.addEventListener('resize', () => {
      [genreChart, consoleChart, gameChart].forEach(safeRender);
    });
    initPaginatedTable('genre-section',   genreData);
    initPaginatedTable('console-section', consoleData);
    initPaginatedTable('game-section',    gameData);
  </script>
</body>
</html>