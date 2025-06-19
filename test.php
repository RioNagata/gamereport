<?php
include 'config.php'; // assumes $link is your mysqli connection

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
  <title>Paginated Table with Count</title>
  <style>
    body {
      font-family: Arial, sans-serif;
      padding: 20px;
    }
    table {
      width: 100%;
      border-collapse: collapse;
      margin-bottom: 10px;
    }
    table, th, td {
      border: 1px solid #ddd;
    }
    th, td {
      padding: 8px;
      text-align: left;
    }
    .pagination {
      display: flex;
      gap: 10px;
      align-items: center;
    }
    .pagination button {
      padding: 5px 10px;
    }
  </style>
</head>
<body>

  <h2>Paginated Table (With Category Count)</h2>

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
