<?php
require_once "../auth/conn.php";

$filter = isset($_GET['filter']) ? $_GET['filter'] : 'All';
$whereClause = "";
if ($filter === 'In') {
    $whereClause = " WHERE il.type = 'In'";
} elseif ($filter === 'Out') {
    $whereClause = " WHERE il.type = 'Out'";
}

$query = "SELECT 
            il.id, 
            p.product_name, 
            il.type, 
            il.quantity, 
            il.remarks,  -- Changed from il.reason to il.remarks
            il.created_at 
          FROM inventory_logs il 
          JOIN products p ON il.product_id = p.id 
          $whereClause
          ORDER BY il.created_at DESC";

$logs = $pdo->query($query)->fetchAll(PDO::FETCH_ASSOC);
$totalIn = $pdo->query("SELECT SUM(quantity) FROM inventory_logs WHERE type = 'In'")->fetchColumn() ?? 0;
$totalOut = $pdo->query("SELECT SUM(quantity) FROM inventory_logs WHERE type = 'Out'")->fetchColumn() ?? 0;

function e($value) { return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8'); }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Track & Reports - Admin Panel</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/style.css">
    <style>
        .report-container { padding: 25px; }
        .summary-cards { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 25px; }
        .s-card { background: white; padding: 15px; border-radius: 12px; display: flex; align-items: center; gap: 15px; box-shadow: 0 4px 10px rgba(0,0,0,0.03); }
        .s-icon { width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 1.2rem; }
        
        .filter-group { display: flex; gap: 10px; margin-bottom: 20px; }
        .filter-btn { padding: 8px 16px; border-radius: 8px; border: 1px solid #ddd; background: white; cursor: pointer; text-decoration: none; color: #666; font-size: 0.9rem; transition: 0.3s; }
        .filter-btn.active { background: #f28c28; color: white; border-color: #f28c28; }

        .report-card { background: white; padding: 20px; border-radius: 15px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); }
        .table-responsive { overflow-x: auto; margin-top: 10px; }
        table { width: 100%; border-collapse: collapse; text-align: left; }
        th { padding: 15px; background: #f8f9fa; color: #7f8c8d; font-size: 0.85rem; text-transform: uppercase; }
        td { padding: 15px; border-bottom: 1px solid #edf2f7; font-size: 0.9rem; }

        .badge { padding: 5px 10px; border-radius: 6px; font-size: 0.7rem; font-weight: bold; }
        .badge-in { background: #e6fffa; color: #27ae60; }
        .badge-out { background: #fff5f5; color: #e53e3e; }
    </style>
</head>
<body>
    <div class="container">
        <aside class="sidebar">
            <div class="sidebar-header"><i class="fa-solid fa-boxes-stacked"></i> <span>Admin Panel</span></div>
             <nav style="flex-grow: 1;">
                <a href="index.php" class="nav-item"><i class="fa-solid fa-chart-line"></i> <span>Dashboard</span></a>
                <a href="inventory.php" class="nav-item"><i class="fa-solid fa-boxes-packing"></i> <span>Inventory</span></a>
                <a href="supplies.php" class="nav-item"><i class="fa-solid fa-truck-ramp-box"></i> <span>Supplies</span></a>
                <a href="track&reports.php" class="nav-item active"><i class="fa-solid fa-route"></i></i> <span>Track & Reports</span></a>
                <a href="view_orders.php" class="nav-item "><i class="fa-solid fa-file-invoice-dollar"></i> <span>view orders</span></a>
                <a href="User-management.php" class="nav-item"><i class="fa-solid fa-users"></i> <span>User Management</span></a>
                <a href="settings.php" class="nav-item"><i class="fa-solid fa-gears"></i> <span>Settings</span></a>
            </nav>
        </aside>

        <main class="main-content">
            <header class="header">
                <div class="header-left">
                    <button id="sidebarToggle" class="hamburger-btn"><i class="fa-solid fa-bars"></i></button>
                    <h1>Track & Reports</h1>
                </div>
            </header>

            <section class="report-container">
                <div class="summary-cards">
                    <div class="s-card">
                        <div class="s-icon" style="background: #e6fffa; color: #27ae60;"><i class="fa-solid fa-arrow-trend-up"></i></div>
                        <div><small style="color:#7f8c8d">Total Stock In</small><div><strong><?= number_format($totalIn) ?> Units</strong></div></div>
                    </div>
                    <div class="s-card">
                        <div class="s-icon" style="background: #fff5f5; color: #e53e3e;"><i class="fa-solid fa-arrow-trend-down"></i></div>
                        <div><small style="color:#7f8c8d">Total Stock Out</small><div><strong><?= number_format($totalOut) ?> Units</strong></div></div>
                    </div>
                </div>

                <div class="filter-group">
                    <a href="?filter=All" class="filter-btn <?= $filter === 'All' ? 'active' : '' ?>">All Activity</a>
                    <a href="?filter=In" class="filter-btn <?= $filter === 'In' ? 'active' : '' ?>">Stock In (Supplies)</a>
                    <a href="?filter=Out" class="filter-btn <?= $filter === 'Out' ? 'active' : '' ?>">Stock Out (Sales)</a>
                </div>

                <div class="report-card">
                    <div class="table-responsive">
                        <table>
                            <thead>
                                <tr>
                                    <th>Date & Time</th>
                                    <th>Product Name</th>
                                    <th>Type</th>
                                    <th>Quantity</th>
                                    <th>Reference/Reason</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (count($logs) > 0): ?>
                                    <?php foreach ($logs as $log): ?>
                                        <tr>
                                            <td style="color:#95a5a6"><?= date('M d, Y | h:i A', strtotime($log['created_at'])) ?></td>
                                            <td><strong><?= e($log['product_name']) ?></strong></td>
                                            <td>
                                                <span class="badge <?= $log['type'] == 'In' ? 'badge-in' : 'badge-out' ?>">
                                                    <?= $log['type'] == 'In' ? 'STOCK IN' : 'STOCK OUT' ?>
                                                </span>
                                            </td>
                                            <td style="font-weight:bold; color: <?= $log['type'] == 'In' ? '#27ae60' : '#e53e3e' ?>">
                                                <?= $log['type'] == 'In' ? '+' : '-' ?> <?= number_format($log['quantity']) ?>
                                            </td>
                                            <td style="font-style: italic; color: #7f8c8d;"><?= e($log['reason']) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr><td colspan="5" style="text-align:center; padding:50px; color:#bdc3c7;">No records found for this filter.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>
        </main>
    </div>
</body>
</html>