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
 
var chart = new CanvasJS.Chart("chartContainer", {
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
chart.render();
 
}
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
          <div class="table-chart-wrapper mb-4">
          <div>
            <table id="data-table">
              <thead>
                <tr>
                  <th>Name</th>
                  <th>Count</th>
                </tr>
              </thead>
              <tbody></tbody>
            </table>

            <div class="pagination">
              <button id="prevBtn">Previous</button>
              <span id="pageInfo"></span>
              <button id="nextBtn">Next</button>
            </div>
          </div>
          <div>
            <div id="chartContainer" style="height: 370px; width: 100%;"></div>
            <script src="https://canvasjs.com/assets/script/canvasjs.min.js"></script>
          </div>
        </div>
    </div>
    </div>
  </div>
  <script>
    const data = <?php echo json_encode($genreResultArray); ?>;
    const rowsPerPage = 10;
    let currentPage = 1;

    function renderTable() {
      const start = (currentPage - 1) * rowsPerPage;
      const end = start + rowsPerPage;
      const paginatedData = data.slice(start, end);

      const tbody = document.querySelector("#data-table tbody");
      tbody.innerHTML = "";

      paginatedData.forEach(row => {
        const tr = document.createElement("tr");
        tr.innerHTML = `<td>${row.name}</td><td>${row.count}</td>`;
        tbody.appendChild(tr);
      });

      document.getElementById("pageInfo").textContent = 
        `Page ${currentPage} of ${Math.ceil(data.length / rowsPerPage)}`;

      document.getElementById("prevBtn").disabled = currentPage === 1;
      document.getElementById("nextBtn").disabled = 
        currentPage === Math.ceil(data.length / rowsPerPage);
    }

    document.getElementById("prevBtn").addEventListener("click", () => {
      if (currentPage > 1) {
        currentPage--;
        renderTable();
      }
    });

    document.getElementById("nextBtn").addEventListener("click", () => {
      if (currentPage < Math.ceil(data.length / rowsPerPage)) {
        currentPage++;
        renderTable();
      }
    });

    renderTable();
  </script>
</body>

</html>