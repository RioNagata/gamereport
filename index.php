<?php 

include 'config.php';

$sevenDays = [];
$sevenDaysPoint = [];

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

for($i=0;$i<7;$i++) {
    $sevenDays[] = date('Y-m-d', strtotime('-'. $i . ' day'));
}

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
arsort($genreResultArray);

foreach($consolearray as $data){
  $consolecount = $link->prepare("SELECT * FROM wp_gf_entry_meta WHERE meta_value = '$data'");
  $consolecount->execute();
  $consoleresult = $consolecount->get_result();
  $consoleresultcount = mysqli_num_rows($consoleresult);
  $consoleResultArray[] = array("y" => $consoleresultcount, "label" => $data);
}
arsort($consoleResultArray);

foreach($gamearray as $data){
  $gamecount = $link->prepare("SELECT * FROM wp_gf_entry_meta WHERE meta_value = '$data'");
  $gamecount->execute();
  $gameresult = $gamecount->get_result();
  $gameresultcount = mysqli_num_rows($gameresult);
  $gameResultArray[] = array("y" => $gameresultcount, "label" => $data);
}
arsort($gameResultArray);
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
	title: {
		text: "Entries for Last 7 Days"
	},
	axisY: {
		title: "Number of Entries"
	},
	data: [{
		type: "line",
		dataPoints: <?php echo json_encode($sevenDaysPoint, JSON_NUMERIC_CHECK); ?>
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

      <div class="chart-box mb-4">
        <div id="chartContainer" style="height: 370px; width: 100%;"></div>
        <script src="https://cdn.canvasjs.com/canvasjs.min.js"></script>
      </div>

      <div class="card-box">
        <div class="card bg-white">
          <h5>Console Ranking</h5>
          <ul class="list-group">
            <?php 
            foreach($consoleResultArray as $games){
              ?><li class="list-group-item"><?php echo $games["label"];?> - <?php echo $games["y"];?></li>
              <?php
            }
            ?>
          </ul>
        </div>
        <div class="card bg-white">
          <h5>Game Ranking</h5>
          <ul class="list-group">
          <?php 
            foreach($gameResultArray as $games){
              ?><li class="list-group-item"><?php echo $games["label"];?> - <?php echo $games["y"];?></li>
              <?php
            }
            ?>
          </ul>
        </div>
      </div>
    </div>
  </div>
</body>

</html>
