<?php 

include 'config.php';
// Get meta_value and count of occurrences
$result = mysqli_query($link, "
  SELECT meta_value, COUNT(*) as count 
  FROM `wp_gf_entry_meta` 
  WHERE `meta_key` = '3' 
  GROUP BY meta_value 
  ORDER BY count DESC
");

$data = [];
if ($result && mysqli_num_rows($result) > 0) {
  while ($row = mysqli_fetch_assoc($result)) {
    $data[] = [
      'name' => $row['meta_value'],
      'count' => $row['count']
    ];
  }
}
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
            <script src="https://cdn.canvasjs.com/canvasjs.min.js"></script>
          </div>
        </div>
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
  <script>
    const data = <?php echo json_encode($data); ?>;
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