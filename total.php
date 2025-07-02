<?php 

include 'config.php';

$gameGenre = mysqli_query($link, "SELECT DISTINCT `meta_value` FROM `wp_gf_entry_meta` WHERE `meta_key` = '4'");
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

foreach($genrearray as $data){
  $genrecount = $link->prepare("SELECT * FROM wp_gf_entry_meta WHERE meta_value = '$data'");
  $genrecount->execute();
  $genreresult = $genrecount->get_result();
  $genreresultcount = mysqli_num_rows($genreresult);
  $genreResultArray[] = array("y" => $genreresultcount, "label" => $data);
}
rsort($genreResultArray);

foreach($consolearray as $data){
  $consolecount = $link->prepare("SELECT * FROM wp_gf_entry_meta WHERE meta_value = '$data'");
  $consolecount->execute();
  $consoleresult = $consolecount->get_result();
  $consoleresultcount = mysqli_num_rows($consoleresult);
  $consoleResultArray[] = array("y" => $consoleresultcount, "label" => $data);
}
rsort($consoleResultArray);

foreach($gamearray as $data){
  $gamecount = $link->prepare("SELECT * FROM wp_gf_entry_meta WHERE meta_value = '$data'");
  $gamecount->execute();
  $gameresult = $gamecount->get_result();
  $gameresultcount = mysqli_num_rows($gameresult);
  $gameResultArray[] = array("y" => $gameresultcount, "label" => $data);
}
rsort($gameResultArray);

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
  <script>
window.onload = function () {
 
var genrechart = new CanvasJS.Chart("chartContainer1", {
	animationEnabled: true,
	theme: "light2",
	title:{
		text: "Top Popular Game Consoles"
	},
	axisY: {
		title: ""
	},
	data: [{
		type: "column",
		dataPoints: <?php echo json_encode($genreResultArray, JSON_NUMERIC_CHECK); ?>
	}]
});
genrechart.render();

var consolechart = new CanvasJS.Chart("chartContainer2", {
	animationEnabled: true,
	theme: "light2",
	title:{
		text: "Top Popular Game Consoles"
	},
	axisY: {
		title: ""
	},
	data: [{
		type: "column",
		dataPoints: <?php echo json_encode($consoleResultArray, JSON_NUMERIC_CHECK); ?>
	}]
});
consolechart.render();

var gamechart = new CanvasJS.Chart("chartContainer3", {
	animationEnabled: true,
	theme: "light2",
	title:{
		text: "Top Popular Game Consoles"
	},
	axisY: {
		title: ""
	},
	data: [{
		type: "column",
		dataPoints: <?php echo json_encode($gameResultArray, JSON_NUMERIC_CHECK); ?>
	}]
});
gamechart.render();
 
}
</script>
<script>
  /* Data straight from PHP */
  const genreData   = <?php echo json_encode($genreResultArray   ?? []); ?>;
  const consoleData = <?php echo json_encode($consoleResultArray ?? []); ?>;
  const gameData    = <?php echo json_encode($gameResultArray    ?? []); ?>;
</script>

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

      <div>
        <h1></h1>
        <!-- ============ TAB NAV ============ -->
<ul class="nav nav-tabs mb-3" id="statsTabs" role="tablist">
  <li class="nav-item" role="presentation">
    <button class="nav-link active" id="genre-tab"   data-bs-toggle="tab" data-bs-target="#genre-pane"
            type="button" role="tab" aria-controls="genre-pane"   aria-selected="true">
      Genres
    </button>
  </li>
  <li class="nav-item" role="presentation">
    <button class="nav-link"        id="console-tab" data-bs-toggle="tab" data-bs-target="#console-pane"
            type="button" role="tab" aria-controls="console-pane" aria-selected="false">
      Consoles
    </button>
  </li>
  <li class="nav-item" role="presentation">
    <button class="nav-link"        id="game-tab"    data-bs-toggle="tab" data-bs-target="#game-pane"
            type="button" role="tab" aria-controls="game-pane"    aria-selected="false">
      Games
    </button>
  </li>
</ul>

<!-- ============ TAB PANES ============ -->
<div class="tab-content">

  <!-- ===== Genres ===== -->
  <div class="tab-pane fade show active" id="genre-pane" role="tabpanel" aria-labelledby="genre-tab">
    <div class="table-chart-wrapper mb-4">
      <div class="paginated-section" id="genre-section">
        <h5 class="mb-2">Top Genres</h5>
        <table class="data-table">
          <thead><tr><th>Name</th><th>Count</th></tr></thead>
          <tbody></tbody>
        </table>
        <div class="pagination">
          <button class="prevBtn">Previous</button>
          <span class="pageInfo"></span>
          <button class="nextBtn">Next</button>
        </div>
      </div>
      <div>
        <div id="chartContainer1" style="height:370px;width:100%;"></div>
      </div>
    </div>
  </div>

  <!-- ===== Consoles ===== -->
  <div class="tab-pane fade" id="console-pane" role="tabpanel" aria-labelledby="console-tab">
    <div class="table-chart-wrapper mb-4">
      <div class="paginated-section" id="console-section">
        <h5 class="mb-2">Top Consoles</h5>
        <table class="data-table">
          <thead><tr><th>Name</th><th>Count</th></tr></thead>
          <tbody></tbody>
        </table>
        <div class="pagination">
          <button class="prevBtn">Previous</button>
          <span class="pageInfo"></span>
          <button class="nextBtn">Next</button>
        </div>
      </div>
      <div>
        <div id="chartContainer2" style="height:370px;width:100%;"></div>
      </div>
    </div>
  </div>

  <!-- ===== Games ===== -->
  <div class="tab-pane fade" id="game-pane" role="tabpanel" aria-labelledby="game-tab">
    <div class="table-chart-wrapper mb-4">
      <div class="paginated-section" id="game-section">
        <h5 class="mb-2">Top Games</h5>
        <table class="data-table">
          <thead><tr><th>Name</th><th>Count</th></tr></thead>
          <tbody></tbody>
        </table>
        <div class="pagination">
          <button class="prevBtn">Previous</button>
          <span class="pageInfo"></span>
          <button class="nextBtn">Next</button>
        </div>
      </div>
      <div>
        <div id="chartContainer3" style="height:370px;width:100%;"></div>
      </div>
    </div>
  </div>

</div><!-- /.tab-content -->

    </div>
  </div>
  <!-- ============ SCRIPTS ============ -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://canvasjs.com/assets/script/canvasjs.min.js"></script>

<script>
/* --- paginator already in your file, just call it again if needed --- */
initPaginatedTable("genre-section",   genreData);
initPaginatedTable("console-section", consoleData);
initPaginatedTable("game-section",    gameData);

/* --- one CanvasJS chart per tab --- */
const charts = {
  genre:   new CanvasJS.Chart("chartGenres",   { animationEnabled:true, theme:"light2", title:{text:"Top Genres"},   data:[{type:"column", dataPoints: genreData}] }),
  console: new CanvasJS.Chart("chartConsoles", { animationEnabled:true, theme:"light2", title:{text:"Top Consoles"}, data:[{type:"column", dataPoints: consoleData}] }),
  game:    new CanvasJS.Chart("chartGames",    { animationEnabled:true, theme:"light2", title:{text:"Top Games"},    data:[{type:"column", dataPoints: gameData}] })
};
charts.genre.render();         // first tab is visible

/* Lazy‑render charts when their tab becomes visible */
document.getElementById('statsTabs').addEventListener('shown.bs.tab', e => {
  const id = e.target.id;                        // genre-tab / console-tab / game-tab
  if (id.startsWith('console')) charts.console.render();
  if (id.startsWith('game'))    charts.game.render();
});
</script>
  <script>
  /* ---------- generic paginator ---------- */
  function initPaginatedTable(sectionId, data, rowsPerPage = 5) {
    let page = 1;
    const section   = document.getElementById(sectionId);
    const tbody     = section.querySelector("tbody");
    const prevBtn   = section.querySelector(".prevBtn");
    const nextBtn   = section.querySelector(".nextBtn");
    const pageInfo  = section.querySelector(".pageInfo");

    const render = () => {
      const start = (page - 1) * rowsPerPage;
      const slice = data.slice(start, start + rowsPerPage);

      tbody.innerHTML = "";
      slice.forEach(r => {
        tbody.insertAdjacentHTML("beforeend",
          `<tr><td>${r.label}</td><td>${r.y}</td></tr>`);
      });

      const pages = Math.max(1, Math.ceil(data.length / rowsPerPage));
      pageInfo.textContent = `Page ${page} of ${pages}`;
      prevBtn.disabled = page === 1;
      nextBtn.disabled = page === pages;
    };

    prevBtn.onclick = () => { if (page > 1) { page--; render(); } };
    nextBtn.onclick = () => {
      const pages = Math.ceil(data.length / rowsPerPage);
      if (page < pages) { page++; render(); }
    };

    render();  // first draw
  }

  /* ---------- kick off each table ---------- */
  initPaginatedTable("genre-section",   genreData);
  initPaginatedTable("console-section", consoleData);
  initPaginatedTable("game-section",    gameData);
</script>

</body>

</html>