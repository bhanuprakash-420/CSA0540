<?php
// DATABASE CONFIGURATION
$host = "localhost";
$user = "root";
$pass = "";
$db = "smart_traffic";

// Step 1: Connect and create DB
$conn = new mysqli($host, $user, $pass);
if ($conn->connect_error) die("❌ Connection failed: " . $conn->connect_error);
$conn->query("CREATE DATABASE IF NOT EXISTS $db");
$conn->select_db($db);

// Step 2: Create table for vehicle logs
$conn->query("CREATE TABLE IF NOT EXISTS vehicle_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    direction VARCHAR(10),
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

// Step 3: Log detected vehicle
if (isset($_GET['direction'])) {
    $direction = $_GET['direction'];
    $stmt = $conn->prepare("INSERT INTO vehicle_logs (direction) VALUES (?)");
    $stmt->bind_param("s", $direction);
    $stmt->execute();
    $stmt->close();
}

// Step 4: Get vehicle counts in the last 2 minutes
$directions = ['North', 'South', 'East', 'West'];
$counts = [];
foreach ($directions as $dir) {
    $res = $conn->query("SELECT COUNT(*) AS total FROM vehicle_logs 
                         WHERE direction='$dir' AND timestamp >= NOW() - INTERVAL 2 MINUTE");
    $row = $res->fetch_assoc();
    $counts[$dir] = $row['total'];
}

// Step 5: Determine which direction gets green light
$greenDirection = array_search(max($counts), $counts);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Smart Traffic Signal</title>
    <style>
        body { font-family: Arial; background: #eef; padding: 20px; }
        .intersection {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 40px;
            margin-bottom: 40px;
        }
        .signal {
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 0 10px #999;
            text-align: center;
            font-size: 20px;
            font-weight: bold;
        }
        .green { background: #28a745; color: white; }
        .red { background: #dc3545; color: white; }
        button {
            padding: 12px 20px;
            margin: 10px;
            font-size: 16px;
            background: #007bff;
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
        }
        button:hover {
            background: #0056b3;
        }
        .logs {
            background: #fff;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 0 8px #ccc;
            max-width: 600px;
            margin: auto;
        }
    </style>
</head>
<body>

<h1>🚦 Smart Traffic Signal Control System</h1>
<p>Green signal is given to the direction with the highest traffic in the last 2 minutes.</p>

<div class="intersection">
    <?php foreach ($directions as $dir): ?>
        <div class="signal <?= $dir == $greenDirection ? 'green' : 'red' ?>">
            <?= $dir ?><br>
            <?= $counts[$dir] ?> vehicle(s)
        </div>
    <?php endforeach; ?>
</div>

<h3>📸 Simulate Vehicle Detection:</h3>
<?php foreach ($directions as $dir): ?>
    <form method="GET" style="display:inline;">
        <input type="hidden" name="direction" value="<?= $dir ?>">
        <button type="submit">Detect at <?= $dir ?></button>
    </form>
<?php endforeach; ?>

<div class="logs">
    <h3>📝 Recent Vehicle Logs</h3>
    <ul>
        <?php
        $logs = $conn->query("SELECT * FROM vehicle_logs ORDER BY timestamp DESC LIMIT 10");
        while ($log = $logs->fetch_assoc()) {
            echo "<li>[{$log['timestamp']}] - Detected at <b>{$log['direction']}</b></li>";
        }
        ?>
    </ul>
</div>

</body>
</html>
