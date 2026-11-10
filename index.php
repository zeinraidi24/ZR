<?php
require_once 'db.php';

$msg = '';
$err = '';

// 1. CREATE JOB
if (isset($_POST['action']) && $_POST['action'] === 'create') {
    $stmt = $pdo->prepare("INSERT INTO customers (name, email, phone) VALUES (?, ?, ?)");
    $stmt->execute([$_POST['name'], $_POST['email'], $_POST['phone']]);
    $c_id = $pdo->lastInsertId();

    $stmt = $pdo->prepare("INSERT INTO items (customer_id, item_name, serial_number) VALUES (?, ?, ?)");
    $stmt->execute([$c_id, $_POST['item_name'], $_POST['serial_number']]);
    $i_id = $pdo->lastInsertId();

    $stmt = $pdo->prepare("INSERT INTO repair_jobs (item_id, issue_description) VALUES (?, ?)");
    $stmt->execute([$i_id, $_POST['issue_description']]);
    $msg = "Job logged successfully!";
}

// 2. UPDATE JOB & EDGE CASE CHECK
if (isset($_POST['action']) && $_POST['action'] === 'update') {
    $notes = trim($_POST['technician_notes']);
    $cost = trim($_POST['repair_cost']);
    
    if ($_POST['status'] === 'Completed' && (empty($notes) || empty($cost))) {
        $err = "Edge Case Warning: Completed jobs require technician notes and repair cost!";
    } else {
        $stmt = $pdo->prepare("UPDATE repair_jobs SET status=?, technician_notes=?, repair_cost=? WHERE id=?");
        $stmt->execute([$_POST['status'], $notes, $cost ?: null, $_POST['job_id']]);
        $msg = "Job updated!";
    }
}

// 3. DELETE JOB
if (isset($_POST['action']) && $_POST['action'] === 'delete') {
    $stmt = $pdo->prepare("DELETE FROM repair_jobs WHERE id=?");
    $stmt->execute([$_POST['job_id']]);
    $msg = "Job deleted!";
}

// FETCH JOBS
$jobs = $pdo->query("
    SELECT r.id AS job_id, r.issue_description, r.status, r.technician_notes, r.repair_cost,
           i.item_name, c.name AS customer_name, c.phone
    FROM repair_jobs r
    JOIN items i ON r.item_id = i.id
    JOIN customers c ON i.customer_id = c.id
    ORDER BY r.id DESC
")->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Repair Job Tracker</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

    <h1>🔧 Repair Tracker</h1>
    <?php if ($err) echo "<p style='color:red;'>$err</p>"; ?>
    <?php if ($msg) echo "<p style='color:green;'>$msg</p>"; ?>

    <div class="container">
        <!-- FORM -->
        <div class="box">
            <h2>Log Job</h2>
            <form method="POST">
                <input type="hidden" name="action" value="create">
                <input type="text" name="name" placeholder="Customer Name *" required><br><br>
                <input type="email" name="email" placeholder="Email *" required><br><br>
                <input type="text" name="phone" placeholder="Phone *" required><br><br>
                <input type="text" name="item_name" placeholder="Item Name *" required><br><br>
                <input type="text" name="serial_number" placeholder="Serial Number"><br><br>
                <textarea name="issue_description" placeholder="Issue *" required></textarea><br><br>
                <button type="submit">Submit</button>
            </form>
        </div>

        <!-- TABLE -->
        <div class="box" style="flex:2;">
            <h2>Current Jobs</h2>
            <table>
                <tr>
                    <th>ID</th><th>Customer</th><th>Item</th><th>Issue</th><th>Status</th><th>Update</th><th>Action</th>
                </tr>
                <?php foreach ($jobs as $j): ?>
                <tr>
                    <td><a href="#" onclick="fetchDetails(<?= $j['job_id'] ?>); return false;">#<?= $j['job_id'] ?></a></td>
                    <td><?= htmlspecialchars($j['customer_name']) ?></td>
                    <td><?= htmlspecialchars($j['item_name']) ?></td>
                    <td><?= htmlspecialchars($j['issue_description']) ?></td>
                    <td><b><?= $j['status'] ?></b></td>
                    <td>
                        <form method="POST">
                            <input type="hidden" name="action" value="update">
                            <input type="hidden" name="job_id" value="<?= $j['job_id'] ?>">
                            <select name="status">
                                <option value="Received">Received</option>
                                <option value="Diagnosing">Diagnosing</option>
                                <option value="In Progress">In Progress</option>
                                <option value="Completed">Completed</option>
                            </select>
                            <input type="text" name="technician_notes" placeholder="Notes">
                            <input type="number" step="0.01" name="repair_cost" placeholder="Cost">
                            <button type="submit">Save</button>
                        </form>
                    </td>
                    <td>
                        <form method="POST" onsubmit="return confirm('Delete job?');">
                            <input type="hidden" name="action" value="delete">
                            <input type="hidden" name="job_id" value="<?= $j['job_id'] ?>">
                            <button type="submit" class="btn-delete">Delete</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </table>
        </div>
    </div>

    <!-- MODAL -->
    <div id="jobModal" class="modal">
        <div class="modal-content">
            <div id="modalBody"></div>
            <br>
            <button onclick="document.getElementById('jobModal').style.display='none'">Close</button>
        </div>
    </div>

    <script src="script.js"></script>
</body>
</html>