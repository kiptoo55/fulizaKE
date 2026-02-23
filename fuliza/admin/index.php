<?php
require_once __DIR__ . '/../config/supabase.php';
require_once __DIR__ . '/../includes/auth_check.php'; // You'd create this

$supabase = SupabaseDB::getInstance();

// Get transaction statistics
$transactions = $supabase->select(
    'transactions',
    [],
    ['column' => 'created_at', 'direction' => 'desc'],
    100
);

// Calculate stats
$totalTransactions = count($transactions);
$completedTransactions = count(array_filter($transactions, fn($t) => $t['status'] == 'completed'));
$totalRevenue = array_sum(array_column(array_filter($transactions, fn($t) => $t['status'] == 'completed'), 'amount'));
?>

<!DOCTYPE html>
<html>
<head>
    <title>Admin Dashboard - FulizaBoost</title>
    <link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body>
    <div class="admin-container">
        <h1>Transaction Dashboard</h1>
        
        <div class="stats-grid">
            <div class="stat-card">
                <h3>Total Transactions</h3>
                <p class="stat-number"><?php echo $totalTransactions; ?></p>
            </div>
            <div class="stat-card">
                <h3>Completed</h3>
                <p class="stat-number"><?php echo $completedTransactions; ?></p>
            </div>
            <div class="stat-card">
                <h3>Total Revenue</h3>
                <p class="stat-number">Ksh <?php echo number_format($totalRevenue, 2); ?></p>
            </div>
        </div>
        
        <table class="transactions-table">
            <thead>
                <tr>
                    <th>Date</th>
                    <th>Phone</th>
                    <th>Limit</th>
                    <th>Amount</th>
                    <th>Receipt</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($transactions as $t): ?>
                <tr>
                    <td><?php echo date('Y-m-d H:i', strtotime($t['created_at'])); ?></td>
                    <td><?php echo substr($t['phone'], 0, 4) . '****' . substr($t['phone'], -2); ?></td>
                    <td>Ksh <?php echo number_format($t['limit_amount']); ?></td>
                    <td>Ksh <?php echo number_format($t['amount']); ?></td>
                    <td><?php echo $t['mpesa_receipt'] ?? '—'; ?></td>
                    <td>
                        <span class="status-badge status-<?php echo $t['status']; ?>">
                            <?php echo $t['status']; ?>
                        </span>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</body>
</html>