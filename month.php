<?php 
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

include 'config.php';
//print_r($_SESSION); 
if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: login.php");
    exit;
}

// get unique month/year values
$sql = "SELECT DISTINCT 
            YEAR(date_created) AS year, 
            MONTHNAME(date_created) AS month_name,
            MONTH(date_created) AS month_number
        FROM wp_gf_entry
        ORDER BY year ASC, month_number ASC";

$result = $link->query($sql);

$dates = [];
while ($row = $result->fetch_assoc()) {
    // Use month_number as the array key
    $dates[$row['year']][$row['month_number']] = $row['month_name'];
}

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
        <h1>Monthly Report</h1>
        <div class="card p-3 mb-3">
        <?php foreach ($dates as $yr => $months): ?>
        <h3 class="mt-3"><?php echo $yr; ?></h3>
        <div class="d-flex flex-wrap gap-2">
            <?php foreach ($months as $mn_number => $mn_name): ?>
                <a href="monthlyreport.php?date=<?php echo $yr . '-' . str_pad($mn_number, 2, '0', STR_PAD_LEFT); ?>"
                class="btn btn-outline-primary btn-lg">
                    <?php echo $mn_name; ?>
                </a>
            <?php endforeach; ?>
        </div>
        <?php endforeach; ?>
        </div>
    </div>
  </div>
</body>

</html>
