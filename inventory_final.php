<?php
$conn = mysqli_connect("localhost", "root", "", "my_db");
// --- 1. SMART ADD/IMPORT --
if (isset($_POST['add_stock'])) {
    $p_name = mysqli_real_escape_string($conn, $_POST['p_name']);
    $qty = $_POST['qty']; $cp = $_POST['cp']; $sp = $_POST['sp'];
    $check = mysqli_query($conn, "SELECT * FROM inventory WHERE p_name = 
'$p_name'");
    if (mysqli_num_rows($check) > 0) {
        mysqli_query($conn, "UPDATE inventory SET quantity = quantity + $qty, 
total_imported = total_imported + $qty, cost_price = $cp, selling_price = $sp 
WHERE p_name = '$p_name'");
    } else {
        mysqli_query($conn, "INSERT INTO inventory (p_name, quantity, 
total_imported, cost_price, selling_price) VALUES ('$p_name', $qty, $qty, $cp, 
$sp)");
    }
}
// --- 2. SELL LOGIC --
if (isset($_POST['sell_item'])) {
    $id = $_POST['prod_id']; $sell_qty = $_POST['sell_qty']; $custom_date = 
$_POST['manual_date'];
    $p_res = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM inventory 
WHERE id = $id"));
    if($p_res['quantity'] >= $sell_qty) {
        $profit = ($p_res['selling_price'] - $p_res['cost_price']) * $sell_qty;
        mysqli_query($conn, "UPDATE inventory SET quantity = quantity - 
$sell_qty WHERE id = $id");
        $date = !empty($custom_date) ? $custom_date : date('Y-m-d H:i:s');
        mysqli_query($conn, "INSERT INTO sales_history (product_id, 
product_name, qty_sold, profit_earned, sale_date) VALUES ($id, 
'{$p_res['p_name']}', $sell_qty, $profit, '$date')");
    }
}
// --- 3. DAMAGE LOGIC --
if (isset($_POST['report_damage'])) {
    $id = $_POST['prod_id']; $dmg_qty = $_POST['dmg_qty'];
    $p_res = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM inventory 
WHERE id = $id"));
    if($p_res['quantity'] >= $dmg_qty) {
        $loss = $p_res['cost_price'] * $dmg_qty;
        mysqli_query($conn, "UPDATE inventory SET quantity = quantity - $dmg_qty
WHERE id = $id");
        mysqli_query($conn, "INSERT INTO damage_logs (product_id, product_name, 
qty_damaged, loss_amount) VALUES ($id, '{$p_res['p_name']}', $dmg_qty, $loss)");
    }
}
// --- 4. GLOBAL DELETE (Inventory + All History) --
if (isset($_GET['remove_product'])) {
    $p_id = $_GET['remove_product'];
    mysqli_query($conn, "DELETE FROM inventory WHERE id = $p_id");
    mysqli_query($conn, "DELETE FROM sales_history WHERE product_id = $p_id");
    mysqli_query($conn, "DELETE FROM damage_logs WHERE product_id = $p_id");
    header("Location: inventory_final.php"); // Page refresh for stats
}
// --- 5. MANUAL HISTORY DELETE --
if (isset($_GET['del_sale'])) {
    mysqli_query($conn, "DELETE FROM sales_history WHERE id = " . 
$_GET['del_sale']);
    header("Location: inventory_final.php");
}
if (isset($_GET['del_dmg'])) {
    mysqli_query($conn, "DELETE FROM damage_logs WHERE id = " . 
$_GET['del_dmg']);
    header("Location: inventory_final.php");
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Grocery Shop - Advanced Analytics</title>
    <style>
        body { font-family: 'Segoe UI', sans-serif; background: #f4f7f6; margin:
0; padding: 20px; }
        .container { max-width: 1300px; margin: auto; background: white; 
padding: 25px; border-radius: 15px; box-shadow: 0 5px 25px rgba(0,0,0,0.1); }
        .dashboard { display: grid; grid-template-columns: repeat(4, 1fr); gap: 
15px; margin-bottom: 30px; }
        .box { padding: 20px; border-radius: 10px; color: white; text-align: 
center; }
        .inv { background: #2c3e50; } .rev { background: #2980b9; } .prof { 
background: #27ae60; } .dmg { background: #c0392b; }
        table { width: 100%; border-collapse: collapse; margin-top: 15px; 
font-size: 13px; }
        th, td { padding: 10px; border: 1px solid #eee; text-align: center; }
        th { background: #34495e; color: white; }
        .del-link { color: #e74c3c; text-decoration: none; font-weight: bold; 
cursor: pointer; }
        .section-title { margin-top: 40px; border-left: 5px solid #3498db; 
padding-left: 10px; color: #2c3e50; }
    </style>
</head>
<body>
<div class="container">
    <h1 style="text-align:center;">Grocery Analytics & Stock Dashboard</h1>
    <?php
    $totals = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(total_imported 
* cost_price) as total_inv, SUM(quantity * selling_price) as pot_rev FROM 
inventory"));
    $earned = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(profit_earned) 
as total_earned FROM sales_history"));
    $loss_res = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(loss_amount) 
as total_loss FROM damage_logs"));
    
    $investment = $totals['total_inv'] ?? 0;
    $net_profit = ($earned['total_earned'] ?? 0) - ($loss_res['total_loss'] ?? 
0);
    ?>
    <div class="dashboard">
        <div class="box inv"><h3>Lifetime Investment</h3><p>₹<?php echo 
number_format($investment, 2); ?></p></div>
        <div class="box rev"><h3>Potential Revenue</h3><p>₹<?php echo 
number_format($totals['pot_rev'] ?? 0, 2); ?></p></div>
        <div class="box prof"><h3>Net Profit Earned</h3><p>₹<?php echo 
number_format($net_profit, 2); ?></p></div>
        <div class="box dmg"><h3>Total Loss (Damage)</h3><p>₹<?php echo 
number_format($loss_res['total_loss'] ?? 0, 2); ?></p></div>
    </div>
    <div style="background:#f9f9f9; padding:20px; border-radius:10px;">
        <h3>Stock Purchase (Import)</h3>
        <form method="POST">
            <input type="text" name="p_name" placeholder="Item Name" required>
            <input type="number" name="qty" placeholder="Qty" required>
            <input type="number" name="cp" placeholder="CP" required>
            <input type="number" name="sp" placeholder="SP" required>
            <button type="submit" name="add_stock" style="background:#2c3e50; 
color:white; padding:10px; border:none; border-radius:5px; 
cursor:pointer;">Update Inventory</button>
        </form>
    </div>
    <h3 class="section-title">Current Inventory Status</h3>
    <table>
        <tr>
            <th>Product</th><th>Lifetime Imported</th><th>Qty in 
Stock</th><th>Unit CP</th><th>Unit SP</th><th>Operations</th><th>Action</th>
        </tr>
        <?php
        $q = mysqli_query($conn, "SELECT * FROM inventory");
        while($r = mysqli_fetch_assoc($q)){
            echo "<tr>
                <td><b>{$r['p_name']}</b></td>
                <td>{$r['total_imported']}</td>
                <td style='background:#fff9e6'>{$r['quantity']}</td>
                <td>₹{$r['cost_price']}</td><td>₹{$r['selling_price']}</td>
                <td>
                    <form method='POST' style='display:inline;'>
                        <input type='hidden' name='prod_id' value='{$r['id']}'>
                        <input type='number' name='sell_qty' 
max='{$r['quantity']}' placeholder='Sell' style='width:50px' required>
                        <input type='date' name='manual_date'>
                        <button type='submit' name='sell_item' 
style='background:#27ae60; color:white; border:none; padding:5px; 
cursor:pointer;'>Sell</button>
                    </form>
                    |
                    <form method='POST' style='display:inline;'>
                        <input type='hidden' name='prod_id' value='{$r['id']}'>
                        <input type='number' name='dmg_qty' 
max='{$r['quantity']}' placeholder='Dmg' style='width:50px' required>
                        <button type='submit' name='report_damage' 
style='background:#e67e22; color:white; border:none; padding:5px; 
cursor:pointer;'>Damage</button>
                    </form>
                </td>
                <td><a href='?remove_product={$r['id']}' class='del-link' 
onclick='return confirm(\"Isse is product ki saari history delete ho jayegi. 
Continue?\")'>Remove</a></td>
            </tr>";
        }
        ?>
    </table>
    <div style="display:grid; grid-template-columns: 1fr 1fr; gap: 20px;">
        <div>
            <h3 class="section-title">Recent Sales History</h3>
            <table>
                <tr style="background:#27ae60; 
color:white;"><th>Date</th><th>Product</th><th>Qty</th><th>Profit</th><th>Delete
</th></tr>
                <?php
                $hist = mysqli_query($conn, "SELECT * FROM sales_history ORDER 
BY id DESC LIMIT 10");
                while($h = mysqli_fetch_assoc($hist)){
                    echo "<tr>
                        <td>".date('d-M', strtotime($h['sale_date']))."</td>
                        <td>{$h['product_name']}</td>
                        <td>{$h['qty_sold']}</td>
                        <td>₹{$h['profit_earned']}</td>
                        <td><a href='?del_sale={$h['id']}' 
class='del-link'>X</a></td>
                    </tr>";
                }
                ?>
            </table>
        </div>
        <div>
            <h3 class="section-title" style="border-left-color:#e74c3c;">Damage 
History (Loss)</h3>
            <table>
                <tr style="background:#e74c3c; 
color:white;"><th>Product</th><th>Qty</th><th>Loss</th><th>Delete</th></tr>
                <?php
                $dmgs = mysqli_query($conn, "SELECT * FROM damage_logs ORDER BY 
id DESC LIMIT 10");
                while($d = mysqli_fetch_assoc($dmgs)){
                    echo "<tr>
                        <td>{$d['product_name']}</td>
                        <td>{$d['qty_damaged']}</td>
                        <td>₹{$d['loss_amount']}</td>
                        <td><a href='?del_dmg={$d['id']}' 
class='del-link'>X</a></td>
                    </tr>";
                }
                ?>
            </table>
        </div>
    </div>
</div>
</body>
</html>
