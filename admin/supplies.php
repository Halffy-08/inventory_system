<?php
require_once "../auth/conn.php";

// Helper for escaping output
function e($value) {
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

// 1. Capture search term
$search = $_GET['search'] ?? '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_supply'])) {
    $supplier = $_POST['supplier_name'];
    $product_id = $_POST['product_id'];
    $quantity = (int)$_POST['quantity'];
    $date_added = $_POST['date_added'];

    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare("INSERT INTO supplies (product_id, supplier_name, quantity_received, date_received) VALUES (?, ?, ?, ?)");
        $stmt->execute([$product_id, $supplier, $quantity, $date_added]);

        $updateStock = $pdo->prepare("UPDATE products SET quantity = quantity + ? WHERE id = ?");
        $updateStock->execute([$quantity, $product_id]);

        $pdo->commit();
        header("Location: supplies.php?success=1");
        exit();
    } catch (Exception $e) {
        $pdo->rollBack();
        die("Error: " . $e->getMessage());
    }
}

// 2. Fetch products for the dropdown
$all_products = $pdo->query("SELECT id, product_name FROM products")->fetchAll();

// 3. Modified Fetch logic for search
try {
    if (!empty($search)) {
        $sql = "SELECT s.*, p.product_name 
                FROM supplies s 
                JOIN products p ON s.product_id = p.id 
                WHERE s.supplier_name LIKE :search 
                OR p.product_name LIKE :search 
                ORDER BY s.date_received DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['search' => "%$search%"]);
    } else {
        $sql = "SELECT s.*, p.product_name 
                FROM supplies s 
                JOIN products p ON s.product_id = p.id 
                ORDER BY s.date_received DESC";
        $stmt = $pdo->query($sql);
    }
    $all_supplies = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Database Error: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Supplies - Admin Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/style.css">
    <style>
        .order-section { padding: 25px; background: #f9f9f9; min-height: 100vh; }
        
        /* Matching Inventory Card Style */
        .supplies-card {
            background: white;
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.08);
        }

        .order-title-bar { 
            display: flex;
            align-items: center;
            gap: 12px;
            color: #2c3e50;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 2px solid #f28c28;
            font-size: 1.4rem;
            font-weight: bold;
        }

        /* --- Search Bar Styling (Matching Inventory) --- */
        .search-container {
            margin-bottom: 25px;
            display: flex;
            justify-content: flex-start;
        }

        .search-box {
            position: relative;
            width: 100%;
            max-width: 400px;
        }

        .search-box i {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #7f8c8d;
        }

        .search-box input {
            width: 100%;
            padding: 12px 15px 12px 45px;
            border: 1px solid #ddd;
            border-radius: 30px;
            font-size: 0.9rem;
            outline: none;
            transition: all 0.3s ease;
            box-sizing: border-box;
        }

        .search-box input:focus {
            border-color: #f28c28;
            box-shadow: 0 0 8px rgba(242, 140, 40, 0.2);
        }

        /* Table Styling */
        .orders-table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        .orders-table th {
            background-color: #f8f9fa;
            color: #7f8c8d;
            text-transform: uppercase;
            font-size: 0.75rem;
            font-weight: 700;
            padding: 15px;
            text-align: center;
            border-bottom: 2px solid #eee;
        }
        .orders-table td { padding: 15px; border-bottom: 1px solid #f1f1f1; text-align: center; color: #444; }
        .orders-table tr:hover { background-color: #fffaf5; }

        /* Modal & Buttons */
        .modal { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); backdrop-filter: blur(3px); justify-content: center; align-items: center; }
        .modal-content { background: #fff; padding: 25px; width: 400px; border-radius: 15px; box-shadow: 0 10px 30px rgba(0,0,0,0.3); }
        .add-btn { width: 100%; background: #f28c28; color: white; border: none; padding: 12px; border-radius: 8px; cursor: pointer; font-weight: bold; margin-top: 15px; }
        .refresh-btn { background: #f28c28; color: white; border: none; padding: 12px 25px; border-radius: 30px; cursor: pointer; font-weight: bold; float: right; margin-top: 20px; margin-left: 10px;}
        .close { float: right; cursor: pointer; font-size: 24px; color: #999; }
    </style>
</head>
<body>

    <div class="container">
        <aside class="sidebar">
            <div class="sidebar-header"><i class="fa-solid fa-boxes-stacked"></i> <span>Clinic System</span></div>
            <nav style="flex-grow: 1;">
                <a href="index.php" class="nav-item"><i class="fa-solid fa-chart-line"></i> <span>Dashboard</span></a>
                <a href="inventory.php" class="nav-item"><i class="fa-solid fa-boxes-packing"></i> <span>Inventory</span></a>
                <a href="supplies.php" class="nav-item active"><i class="fa-solid fa-truck-ramp-box"></i> <span>Supplies</span></a>
                <a href="track&reports.php" class="nav-item"><i class="fa-solid fa-route"></i> <span>Track & Reports</span></a>
                <a href="view_orders.php" class="nav-item "><i class="fa-solid fa-file-invoice-dollar"></i> <span>View Orders</span></a>
                <a href="User-management.php" class="nav-item"><i class="fa-solid fa-users"></i> <span>User Management</span></a>
                <a href="settings.php" class="nav-item"><i class="fa-solid fa-gears"></i> <span>Settings</span></a>
            </nav>
            <div class="sidebar-footer">
                <a href="../auth/logout.php" class="nav-item"><i class="fa-solid fa-right-from-bracket icon"></i> <span>Logout</span></a>
            </div>
        </aside>

        <main class="main-content">
            <header class="header">
                <div class="header-left">
                    <button id="sidebarToggle" class="hamburger-btn"><i class="fa-solid fa-bars"></i></button>
                    <h1>Supplies Management</h1>
                </div>
            </header>

            <section class="order-section">
                <div class="supplies-card">
                    <div class="order-title-bar"><i class="fa-solid fa-truck-field"></i> List Of Supplies Added</div>

                    <?php if(isset($_GET['success'])): ?>
                        <div style="background: #d4edda; color: #155724; padding: 10px; border-radius: 5px; margin-bottom: 20px; font-size: 0.9rem;">
                            <i class="fa-solid fa-check-circle"></i> Stock updated successfully!
                        </div>
                    <?php endif; ?>

                    <div class="search-container">
                        <form action="supplies.php" method="GET" class="search-box">
                            <i class="fa-solid fa-magnifying-glass"></i>
                            <input type="text" name="search" value="<?= e($search) ?>" placeholder="Search supplier or product and press Enter...">
                        </form>
                    </div>

                    <table class="orders-table">
                        <thead>
                            <tr>
                                <th>Supply ID</th>
                                <th>Supplier Name</th>
                                <th>Product Name</th>
                                <th>Quantity</th>
                                <th>Date Received</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($all_supplies)): ?>
                                <?php foreach($all_supplies as $supply): ?>
                                <tr>
                                    <td><strong>#<?= $supply['supply_id'] ?></strong></td>
                                    <td><?= e($supply['supplier_name']) ?></td>
                                    <td><span style="background:#eee; padding:4px 8px; border-radius:4px; font-size:0.8rem;"><?= e($supply['product_name']) ?></span></td>
                                    <td><span style="color: #2ecc71; font-weight: bold;">+<?= $supply['quantity_received'] ?></span></td>
                                    <td><?= $supply['date_received'] ?></td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="5">No supply records found matching "<?= e($search) ?>"</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>

                    <button class="refresh-btn" onclick="openForm()"><i class="fa-solid fa-plus"></i> Add Supplies</button>
                    <button class="refresh-btn" style="background: #7f8c8d;" onclick="location.href='supplies.php'"><i class="fa-solid fa-rotate"></i> Reset</button>
                </div>

                <div id="popupForm" class="modal">
                    <div class="modal-content">
                        <span class="close" onclick="closeForm()">&times;</span>
                        <h2 style="color: #f28c28; margin-top: 0; border-bottom: 1px solid #eee; padding-bottom: 10px;">Add Stock</h2>

                        <form method="POST">
                            <label>Supplier Name:</label>
                            <input type="text" name="supplier_name" placeholder="Who sent this?" required>

                            <label>Product:</label>
                            <select name="product_id" required style="width: 100%; padding: 10px; margin: 5px 0; border-radius: 5px; border: 1px solid #ddd;">
                                <option value="">-- Select Product --</option>
                                <?php foreach($all_products as $p): ?>
                                    <option value="<?= $p['id'] ?>"><?= e($p['product_name']) ?></option>
                                <?php endforeach; ?>
                            </select>

                            <label>Quantity to Add:</label>
                            <input type="number" name="quantity" min="1" required>

                            <label>Date Received:</label>
                            <input type="date" name="date_added" value="<?= date('Y-m-d') ?>" required>

                            <button type="submit" name="add_supply" class="add-btn">Add to Inventory</button>
                        </form>
                    </div>
                </div>
            </section>
        </main>
    </div>

    <script>
        document.getElementById('sidebarToggle').addEventListener('click', () => {
            document.querySelector('.sidebar').classList.toggle('collapsed');
        });

        function openForm() { document.getElementById("popupForm").style.display = "flex"; }
        function closeForm() { document.getElementById("popupForm").style.display = "none"; }
        
        window.onclick = function(event) {
            if (event.target == document.getElementById("popupForm")) { closeForm(); }
        }
    </script>
</body>
</html>