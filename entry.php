<?php
session_start();
include 'config.php';

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if(!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true){
    header("location: login.php");
    exit;
}

// ----------------------
// Field labels
// ----------------------
$fieldLabels = [
    "1.3" => "First Name",
    "1.6" => "Last Name",
    "2"   => "Email",
    "3"   => "Console",
    "4"   => "Genre",
    "5"   => "Budget Range",
    "6"   => "Include Console?",
    "7"   => "Notes",
    "8"   => "Preferred Franchise",
    "9"   => "Delivery Method",
    "10"  => "Monthly Game Pass"
];

$filterableCols = ["date_created" => "Date Created"] + $fieldLabels;

// ----------------------
// Filtering logic (same as before)
// ----------------------
$sqlFilterJoin = "";
$where = "WHERE 1=1";
$params = [];
$types = "";

if (!empty($_GET['column']) && !empty($_GET['operator']) && isset($_GET['value'])) {
    $col = $_GET['column'];
    $op  = $_GET['operator'];
    $val = $_GET['value'];

    if ($col === "date_created") {
        $where .= " AND e.date_created $op ?";
        $params[] = $val;
        $types .= "s";
    } elseif (array_key_exists($col, $fieldLabels)) {
        $sqlFilterJoin = "INNER JOIN wp_gf_entry_meta mf
                          ON e.id = mf.entry_id
                          AND mf.meta_key = ?
                          AND mf.meta_value $op ?";
        $params[] = $col;
        $params[] = ($op === "LIKE") ? "%$val%" : $val;
        $types .= "ss";
    }
}

// ----------------------
// Sorting logic
// ----------------------
$sortCol = $_GET['sort'] ?? 'date_created';   // default
$sortDir = strtoupper($_GET['dir'] ?? 'DESC'); // default

$allowedSortCols = ["date_created"] + $fieldLabels; // only allow valid cols
if (!array_key_exists($sortCol, $allowedSortCols)) {
    $sortCol = "date_created";
}
$sortDir = ($sortDir === "ASC") ? "ASC" : "DESC"; // sanitize

// If sorting by meta column
$orderClause = "";
if ($sortCol === "date_created") {
    $orderClause = "ORDER BY e.date_created $sortDir";
} else {
    // sort by specific meta_key
    $sqlFilterJoin .= " LEFT JOIN wp_gf_entry_meta sm 
                        ON e.id = sm.entry_id AND sm.meta_key = ?";
    $params[] = $sortCol;
    $types .= "s";
    $orderClause = "ORDER BY sm.meta_value $sortDir";
}

// ----------------------
// Query entries
// ----------------------
$sql = "SELECT e.id, e.date_created, m.meta_key, m.meta_value
        FROM wp_gf_entry e
        $sqlFilterJoin
        LEFT JOIN wp_gf_entry_meta m ON e.id = m.entry_id
        $where
        $orderClause";

$stmt = $link->prepare($sql);
if ($params) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$result = $stmt->get_result();

// ----------------------
// Organize results
// ----------------------
$entries = [];
$allMetaKeys = [];

while ($row = $result->fetch_assoc()) {
    $id = $row['id'];
    if (!isset($entries[$id])) {
        $entries[$id] = [
            'id' => $id,
            'date_created' => $row['date_created'],
            'meta' => []
        ];
    }
    $entries[$id]['meta'][$row['meta_key']] = $row['meta_value'];
    if (!empty($row['meta_key']) && !in_array($row['meta_key'], ['form_id','status'])) {
        if (!in_array($row['meta_key'], $allMetaKeys)) {
            $allMetaKeys[] = $row['meta_key'];
        }
    }
}
sort($allMetaKeys);
?>



<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>All Entries</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="d-flex">
    <?php include 'navigation.php'?>
    <div class="content w-100">
  <h2>All Entries</h2>
<form method="get" style="margin-bottom:20px;">
  <label for="column">Column:</label>
  <select name="column" id="column">
    <?php foreach ($filterableCols as $key => $label): ?>
      <option value="<?= htmlspecialchars($key) ?>"
        <?= (isset($_GET['column']) && $_GET['column'] === $key) ? 'selected' : '' ?>>
        <?= htmlspecialchars($label) ?>
      </option>
    <?php endforeach; ?>
  </select>

  <label for="operator">Operator:</label>
  <select name="operator" id="operator">
    <option value="=" <?= ($_GET['operator'] ?? '') === '=' ? 'selected' : '' ?>>Equals</option>
    <option value="!=" <?= ($_GET['operator'] ?? '') === '!=' ? 'selected' : '' ?>>Not Equals</option>
    <option value=">" <?= ($_GET['operator'] ?? '') === '>' ? 'selected' : '' ?>>Greater Than</option>
    <option value="<" <?= ($_GET['operator'] ?? '') === '<' ? 'selected' : '' ?>>Less Than</option>
    <option value="LIKE" <?= ($_GET['operator'] ?? '') === 'LIKE' ? 'selected' : '' ?>>Contains</option>
  </select>

  <label for="value">Value:</label>
  <input type="text" name="value" id="value" value="<?= htmlspecialchars($_GET['value'] ?? '') ?>" placeholder="Enter value">

  <button type="submit">Apply Filter</button>
</form>



<table class="table table-bordered table-striped">
  <thead>
    <tr>
        <th>
            <a href="?sort=id&dir=<?php echo ($sortCol=='id' && $sortDir=='ASC') ? 'DESC':'ASC'; ?>">
                Entry ID
            </a>
        </th>
        <th>
            <a href="?sort=date_created&dir=<?php echo ($sortCol=='date_created' && $sortDir=='ASC') ? 'DESC':'ASC'; ?>">
                Date Created
            </a>
        </th>
        <?php foreach ($fieldLabels as $key => $label): ?>
            <th>
                <a href="?sort=<?php echo urlencode($key); ?>&dir=<?php echo ($sortCol==$key && $sortDir=='ASC') ? 'DESC':'ASC'; ?>">
                    <?php echo htmlspecialchars($label); ?>
                </a>
            </th>
        <?php endforeach; ?>
    </tr>
  </thead>
  <tbody id="entryTable">
    <?php foreach ($entries as $entry): ?>
        <tr>
            <td><?php echo htmlspecialchars($entry['id']); ?></td>
            <td><?php echo htmlspecialchars($entry['date_created']); ?></td>
            <?php foreach ($fieldLabels as $key => $label): ?>
                <td><?php echo htmlspecialchars($entry['meta'][$key] ?? ''); ?></td>
            <?php endforeach; ?>
        </tr>
    <?php endforeach; ?>
  </tbody>
</table>

<script>
document.getElementById("searchInput").addEventListener("keyup", function() {
  let filter = this.value.toLowerCase();
  let rows = document.querySelectorAll("#entryTable tr");
  
  rows.forEach(row => {
    let text = row.textContent.toLowerCase();
    row.style.display = text.includes(filter) ? "" : "none";
  });
});
</script>

</div>
</div>
</body>
</html>
