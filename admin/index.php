<?php
require_once "../add_products/dym.php";


?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Admin Panel</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/style.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .dashboard-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; padding: 25px; }
        .stat-card { background: white; padding: 25px; border-radius: 15px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); position: relative; overflow: hidden; }
        .stat-card h3 { color: #7f8c8d; font-size: 0.9rem; margin-bottom: 10px; text-transform: uppercase; }
        .stat-card .value { font-size: 1.8rem; font-weight: bold; color: #2c3e50; }
        .card-icon { position: absolute; right: -10px; bottom: -10px; font-size: 4rem; opacity: 0.05; color: #f28c28; }

        /* Breakdown Styling */
        .sales-breakdown { display: flex; gap: 10px; margin-top: 15px; padding-top: 10px; border-top: 1px solid #f1f1f1; }
        .sales-sub { flex: 1; }
        .sales-sub label { font-size: 0.65rem; color: #95a5a6; display: block; text-transform: uppercase; margin-bottom: 2px; }
        .sales-sub span { font-size: 0.85rem; font-weight: 600; color: #2c3e50; }

        .bottom-row { display: grid; grid-template-columns: 1.5fr 1fr; gap: 20px; padding: 0 25px 25px 25px; }
        @media (max-width: 992px) { .bottom-row { grid-template-columns: 1fr; } }

        .chart-box, .recent-orders-box { background: white; padding: 20px; border-radius: 15px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); }
        .recent-orders-box h2, .chart-box h2 { font-size: 1.1rem; margin-bottom: 15px; color: #2c3e50; display: flex; align-items: center; gap: 10px; }
        
        .order-row { display: flex; justify-content: space-between; align-items: center; padding: 12px 0; border-bottom: 1px solid #f8f9fa; }
        .order-row:last-child { border-bottom: none; }
        .status-badge { padding: 4px 10px; border-radius: 20px; font-size: 0.7rem; font-weight: bold; text-transform: uppercase; }
        .status-pending { background: #fff4e6; color: #f28c28; }
        .status-approved { background: #e6fffa; color: #27ae60; }
    </style>
</head>
<body>
    <div class="container">
        <aside class="sidebar">
            <div class="sidebar-search" style="padding: 10px 15px;">
                <div style="position: relative; display: flex; align-items: center; background: rgba(255,255,255,0.1); border-radius: 8px; padding: 5px 10px;">
                    <i class="fa-solid fa-magnifying-glass" style="color: #bdc3c7; font-size: 0.8rem;"></i>
                    <input type="text" id="menuSearch" placeholder="Search menu..." onkeyup="filterMenu()" 
                     style="background: none; border: none; color: white; outline: none; margin-left: 10px; width: 100%; font-size: 0.85rem;">
                </div>
            </div>
            <div class="sidebar-header"><i class="fa-solid fa-boxes-stacked"></i> <span>Admin Panel</span></div>
           <nav style="flex-grow: 1;">
                <a href="index.php" class="nav-item active"><i class="fa-solid fa-chart-line"></i> <span>Dashboard</span></a>
                <a href="inventory.php" class="nav-item"><i class="fa-solid fa-boxes-packing"></i> <span>Inventory</span></a>
                <a href="supplies.php" class="nav-item"><i class="fa-solid fa-truck-ramp-box"></i> <span>Supplies</span></a>
                <a href="track&reports.php" class="nav-item"><i class="fa-solid fa-route"></i></i> <span>Track & Reports</span></a>
                <a href="view_orders.php" class="nav-item "><i class="fa-solid fa-file-invoice-dollar"></i> <span>view orders</span></a>
                <a href="User-management.php" class="nav-item"><i class="fa-solid fa-users"></i> <span>User Management</span></a>
                <a href="settings.php" class="nav-item"><i class="fa-solid fa-gears"></i> <span>Settings</span></a>
            </nav>
            <div class="sidebar-footer">
                <a href="../auth/logout.php" class="nav-item"><i class="fa-solid fa-right-from-bracket"></i> <span>Logout</span></a>
            </div>
        </aside>
        <main class="main-content">
            <header class="header">
                <div class="header-left">
                    <button id="sidebarToggle" class="hamburger-btn"><i class="fa-solid fa-bars"></i></button>
                    <h1>Admin Dashboard</h1>
                </div>
            </header>

            <div class="dashboard-grid">
                
                <div class="stat-card">
                    <h3>Revenue Overview</h3>
                    <div class="value">₱<?= number_format($totalSales, 2) ?></div>
                    
                    <div class="sales-breakdown">
                        <div class="sales-sub">
                            <label>Daily</label>
                            <span>₱<?= number_format($dailySales, 0) ?></span>
                        </div>

                        <div class="sales-sub">
                            <label>Monthly</label>
                            <?php if ($daysSinceStart >= 30): ?>
                                <span>₱<?= number_format($monthlySales, 0) ?></span>
                            <?php else: ?>
                                <span style="color: #bdc3c7; font-size: 0.7rem;"><i class="fa-solid fa-lock"></i> Wait <?= 30 - $daysSinceStart ?>d</span>
                            <?php endif; ?>
                        </div>

                        <div class="sales-sub">
                            <label>Yearly</label>
                            <?php if ($daysSinceStart >= 365): ?>
                                <span>₱<?= number_format($yearlySales, 0) ?></span>
                            <?php else: ?>
                                <span style="color: #bdc3c7; font-size: 0.7rem;"><i class="fa-solid fa-lock"></i> Locked</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <i class="fa-solid fa-sack-dollar card-icon"></i>
                </div>

                <div class="stat-card">
                    <h3>Active Products</h3>
                    <div class="value"><?= $productCount ?></div>
                    <i class="fa-solid fa-box card-icon"></i>
                </div>

                <div class="stat-card">
                    <h3>Stock Alerts</h3>
                    <div class="value" style="color: <?= $lowStockCount > 0 ? '#e74c3c' : '#27ae60' ?>;"><?= $lowStockCount ?></div>
                    <p style="color: #7f8c8d; font-size: 0.8rem;">Items near or below 15% stock</p>
                    <i class="fa-solid fa-bell card-icon"></i>
                </div>

            </div> <div class="bottom-row">
                <div class="chart-box">
                    <h2><i class="fa-solid fa-chart-line" style="color: #f28c28;"></i> Sales Trend</h2>
                    <canvas id="salesTrendChart" style="max-height: 280px;"></canvas>
                </div>

                <div class="recent-orders-box">
                    <h2><i class="fa-solid fa-clock-rotate-left" style="color: #f28c28;"></i> Recent Activity</h2>
                    <?php if (count($recentOrders) > 0): ?>
                        <?php foreach ($recentOrders as $order): ?>
                            <div class="order-row">
                                <div style="font-size: 0.85rem;">
                                    <strong><?= e($order['customer_name']) ?></strong><br>
                                    <span style="color: #7f8c8d;"><?= e($order['product_name']) ?></span>
                                </div>
                                <span class="status-badge status-<?= strtolower($order['status']) ?>">
                                    <?= e($order['status']) ?>
                                </span>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p style="color: #999; text-align: center;">No orders yet.</p>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>

    <script>
        document.getElementById('sidebarToggle').addEventListener('click', () => {
            document.querySelector('.sidebar').classList.toggle('collapsed');
        });

        const ctx = document.getElementById('salesTrendChart').getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: <?= json_encode($labels) ?>,
                datasets: [{
                    label: 'Sales (₱)',
                    data: <?= json_encode($values) ?>,
                    borderColor: '#f28c28',
                    backgroundColor: 'rgba(242, 140, 40, 0.1)',
                    borderWidth: 3,
                    fill: true,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: { 
                    y: { beginAtZero: true, grid: { display: false } },
                    x: { grid: { display: false } }
                }
            }
        });
    </script>
</body>
</html>