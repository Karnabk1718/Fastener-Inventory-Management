<?php
session_start();
include "db.php";
include "manager_auth.php";

// Quick summary numbers for report cards
$today        = date('Y-m-d');
$thisMonth    = date('Y-m');
$thisYear     = date('Y');

$ordersToday  = mysqli_fetch_row(mysqli_query($conn,"SELECT COUNT(*) FROM orders WHERE DATE(order_date)='$today'"))[0];
$ordersMonth  = mysqli_fetch_row(mysqli_query($conn,"SELECT COUNT(*) FROM orders WHERE DATE_FORMAT(order_date,'%Y-%m')='$thisMonth'"))[0];
$ordersYear   = mysqli_fetch_row(mysqli_query($conn,"SELECT COUNT(*) FROM orders WHERE YEAR(order_date)='$thisYear'"))[0];
$lowStock     = mysqli_fetch_row(mysqli_query($conn,"SELECT COUNT(*) FROM stock WHERE quantity < 20"))[0];
$totalFast    = mysqli_fetch_row(mysqli_query($conn,"SELECT COUNT(*) FROM fastener"))[0];
$totalSup     = mysqli_fetch_row(mysqli_query($conn,"SELECT COUNT(*) FROM supplier"))[0];
$invValue     = mysqli_fetch_row(mysqli_query($conn,"SELECT COALESCE(SUM(f.unit_price*s.quantity),0) FROM stock s JOIN fastener f ON s.fastener_id=f.id"))[0];
$allOrders    = mysqli_fetch_row(mysqli_query($conn,"SELECT COUNT(*) FROM orders"))[0];
$pendingCnt   = mysqli_fetch_row(mysqli_query($conn,"SELECT COUNT(*) FROM orders WHERE status='Pending'"))[0];
$deliveredCnt = mysqli_fetch_row(mysqli_query($conn,"SELECT COUNT(*) FROM orders WHERE status='Delivered'"))[0];

$monthlyChartRes = mysqli_query($conn,"
    SELECT DATE_FORMAT(order_date,'%b %Y') AS mo, COUNT(*) AS cnt
    FROM orders
    WHERE order_date >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
    GROUP BY YEAR(order_date), MONTH(order_date)
    ORDER BY YEAR(order_date), MONTH(order_date)
");
$monthLabels = []; $monthCounts = [];
while($r = mysqli_fetch_assoc($monthlyChartRes)) { $monthLabels[] = $r['mo']; $monthCounts[] = $r['cnt']; }

$topRes = mysqli_query($conn,"
    SELECT f.name, s.quantity FROM stock s
    JOIN fastener f ON s.fastener_id=f.id
    ORDER BY s.quantity DESC LIMIT 5
");
$topNames = []; $topQtys = [];
while($r = mysqli_fetch_assoc($topRes)) { $topNames[] = $r['name']; $topQtys[] = $r['quantity']; }

$supplierOrderRes = mysqli_query($conn,"
    SELECT s.name, COUNT(*) AS cnt
    FROM orders o
    JOIN supplier s ON o.supplier_id=s.id
    GROUP BY s.id, s.name
    ORDER BY cnt DESC, s.name ASC
    LIMIT 6
");
$supplierNames = []; $supplierCounts = [];
while($r = mysqli_fetch_assoc($supplierOrderRes)) { $supplierNames[] = $r['name']; $supplierCounts[] = (int)$r['cnt']; }

$valueRes = mysqli_query($conn,"
    SELECT f.name, COALESCE(f.unit_price * s.quantity,0) AS value
    FROM stock s
    JOIN fastener f ON s.fastener_id=f.id
    ORDER BY value DESC
    LIMIT 6
");
$valueNames = []; $valueTotals = [];
while($r = mysqli_fetch_assoc($valueRes)) { $valueNames[] = $r['name']; $valueTotals[] = round((float)$r['value'], 2); }

$typeRes = mysqli_query($conn,"
    SELECT COALESCE(NULLIF(TRIM(type),''),'Unspecified') AS type_name, COUNT(*) AS cnt
    FROM fastener
    GROUP BY type_name
    ORDER BY cnt DESC, type_name ASC
    LIMIT 6
");
$typeNames = []; $typeCounts = [];
while($r = mysqli_fetch_assoc($typeRes)) { $typeNames[] = $r['type_name']; $typeCounts[] = (int)$r['cnt']; }

$healthyStock = mysqli_fetch_row(mysqli_query($conn,"SELECT COUNT(*) FROM stock WHERE quantity >= 20"))[0];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Reports – Manager – Bolt Base</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600&family=DM+Mono:wght@500&display=swap" rel="stylesheet">
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.min.js"></script>
<link rel="stylesheet" href="css/manager.css">

</head>
<body class="page-manager_reports">
<div class="navbar">
    <div class="logo">
        <div class="logo-icon"><i class="fa-solid fa-screwdriver-wrench" style="color:var(--gold);font-size:13px"></i></div>
        BOLT BASE &nbsp;<span class="role-chip"><i class="fa fa-user-tie"></i> Manager</span>
    </div>
    <div class="nav-links">
        <a href="manager_dashboard.php">Dashboard</a>
        <a href="manager_fasteners.php">Fasteners</a>
        <a href="manager_inventory.php">Inventory</a>
        <a href="manager_suppliers.php">Suppliers</a>
        <a href="manager_orders.php">Orders</a>
        <a href="manager_reports.php" class="active">Reports</a>
    </div>
    <div class="nav-right">
        <div class="pill"><i class="fa fa-calendar"></i><span id="dateStr"></span></div>
        <div class="pill"><i class="fa fa-user-tie"></i><?= htmlspecialchars($_SESSION['username']) ?></div>
        <div class="icon-btn" onclick="toggleDark()"><i class="fa-solid fa-moon"></i></div>
        <a href="logout.php" class="logout-btn">Logout</a>
    </div>
</div>

<div class="main">
    <h1><i class="fa fa-file-chart-column" style="color:var(--gold)"></i> Reports Center</h1>
    <p class="sub">Generate and download PDF reports — daily, monthly, yearly or custom range</p>

    <div class="sec-head"><i class="fa fa-chart-bar"></i> Visual Analytics</div>
    <div class="charts-row">
        <div class="chart-card">
            <h3>Orders - Last 6 Months</h3>
            <canvas id="monthChart" height="200"></canvas>
        </div>
        <div class="chart-card">
            <h3>Top 5 Fasteners by Stock</h3>
            <canvas id="topChart" height="200"></canvas>
        </div>
        <div class="chart-card">
            <h3>Order Status</h3>
            <canvas id="statusChart" height="200"></canvas>
        </div>
    </div>
    <div class="charts-row extra">
        <div class="chart-card">
            <h3>Orders by Supplier</h3>
            <canvas id="supplierChart" height="190"></canvas>
        </div>
        <div class="chart-card">
            <h3>Inventory Value by Fastener</h3>
            <canvas id="valueChart" height="190"></canvas>
        </div>
        <div class="chart-card">
            <h3>Fastener Type Mix</h3>
            <canvas id="typeChart" height="190"></canvas>
        </div>
        <div class="chart-card">
            <h3>Stock Health</h3>
            <canvas id="stockHealthChart" height="190"></canvas>
        </div>
    </div>

    <!-- Time-based Order Reports -->
    <div class="sec-head"><i class="fa fa-cart-shopping"></i> Order Reports</div>
    <div class="reports-grid">
        <div class="report-card">
            <div class="report-card-icon" style="background:rgba(96,165,250,0.18);color:#60a5fa"><i class="fa fa-calendar-day"></i></div>
            <h3>Today's Orders</h3>
            <p>Orders placed on <?= date('d M Y') ?></p>
            <div class="stat"><?= $ordersToday ?></div>
            <a href="manager_report_export.php?type=daily" class="btn-dl"><i class="fa fa-file-pdf"></i> Download</a>
        </div>
        <div class="report-card">
            <div class="report-card-icon" style="background:rgba(167,139,250,0.18);color:#a78bfa"><i class="fa fa-calendar-week"></i></div>
            <h3>This Month's Orders</h3>
            <p><?= date('F Y') ?> order summary</p>
            <div class="stat"><?= $ordersMonth ?></div>
            <a href="manager_report_export.php?type=monthly" class="btn-dl"><i class="fa fa-file-pdf"></i> Download</a>
        </div>
        <div class="report-card">
            <div class="report-card-icon" style="background:rgba(251,191,36,0.18);color:#fbbf24"><i class="fa fa-calendar"></i></div>
            <h3>This Year's Orders</h3>
            <p><?= date('Y') ?> annual order report</p>
            <div class="stat"><?= $ordersYear ?></div>
            <a href="manager_report_export.php?type=yearly" class="btn-dl"><i class="fa fa-file-pdf"></i> Download</a>
        </div>
    </div>

    <!-- Inventory & Stock Reports -->
    <div class="sec-head"><i class="fa fa-boxes-stacked"></i> Inventory Reports</div>
    <div class="reports-grid">
        <div class="report-card">
            <div class="report-card-icon" style="background:rgba(74,222,128,0.18);color:#4ade80"><i class="fa fa-boxes-stacked"></i></div>
            <h3>Full Inventory</h3>
            <p>Current stock levels for all fasteners</p>
            <div class="stat">₹<?= number_format($invValue, 0) ?></div>
            <a href="manager_report_export.php?type=inventory" class="btn-dl"><i class="fa fa-file-pdf"></i> Download</a>
        </div>
        <div class="report-card">
            <div class="report-card-icon" style="background:rgba(255,107,107,0.18);color:#ff6b6b"><i class="fa fa-triangle-exclamation"></i></div>
            <h3>Low Stock Alert</h3>
            <p>Items below minimum (20 units)</p>
            <div class="stat" style="color:#ff6b6b"><?= $lowStock ?></div>
            <a href="manager_report_export.php?type=low_stock" class="btn-dl"><i class="fa fa-file-pdf"></i> Download</a>
        </div>
        <div class="report-card">
            <div class="report-card-icon" style="background:rgba(255,215,0,0.18);color:var(--gold)"><i class="fa fa-screwdriver-wrench"></i></div>
            <h3>Fastener Catalog</h3>
            <p>Full catalog with prices and specs</p>
            <div class="stat"><?= $totalFast ?></div>
            <a href="manager_report_export.php?type=fasteners" class="btn-dl"><i class="fa fa-file-pdf"></i> Download</a>
        </div>
        <div class="report-card">
            <div class="report-card-icon" style="background:rgba(96,165,250,0.18);color:#60a5fa"><i class="fa fa-industry"></i></div>
            <h3>Supplier List</h3>
            <p>All suppliers with contact details</p>
            <div class="stat"><?= $totalSup ?></div>
            <a href="manager_report_export.php?type=supplier" class="btn-dl"><i class="fa fa-file-pdf"></i> Download</a>
        </div>
        <div class="report-card">
            <div class="report-card-icon" style="background:rgba(167,139,250,0.18);color:#a78bfa"><i class="fa fa-cart-shopping"></i></div>
            <h3>All Orders</h3>
            <p>Complete order history export</p>
            <div class="stat"><?= mysqli_fetch_row(mysqli_query($conn,"SELECT COUNT(*) FROM orders"))[0] ?></div>
            <a href="manager_report_export.php?type=orders" class="btn-dl"><i class="fa fa-file-pdf"></i> Download</a>
        </div>
    </div>

</div>
<script>
function tick(){const n=new Date();document.getElementById('dateStr').textContent=n.toLocaleDateString('en-IN');const timeEl = document.getElementById('timeStr'); if (timeEl) timeEl.textContent = n.toLocaleTimeString('en-IN');}
setInterval(tick,1000);tick();
function toggleDark(){document.body.classList.toggle('dark');localStorage.setItem('boltTheme',document.body.classList.contains('dark')?'dark':'light');updateChartsTheme();}
if(localStorage.getItem('boltTheme')==='dark')document.body.classList.add('dark');

function chartTheme(){
    const dark=document.body.classList.contains('dark');
    return {
        tick: dark ? '#fffde7' : '#3D4127',
        grid: dark ? 'rgba(233,239,184,0.18)' : 'rgba(61,65,39,0.14)',
        monthBg: dark ? 'rgba(212,222,149,0.78)' : 'rgba(99,107,47,0.68)',
        monthBorder: dark ? '#D4DE95' : '#636B2F',
        topBg: dark ? 'rgba(186,192,149,0.82)' : 'rgba(61,65,39,0.68)',
        topBorder: dark ? '#BAC095' : '#3D4127',
        pending: dark ? 'rgba(255,224,102,0.86)' : 'rgba(251,191,36,0.75)',
        delivered: dark ? 'rgba(174,213,129,0.86)' : 'rgba(74,222,128,0.75)',
        supplierBg: dark ? 'rgba(248,250,223,0.78)' : 'rgba(99,107,47,0.62)',
        supplierBorder: dark ? '#F8FADF' : '#636B2F',
        valueBg: dark ? 'rgba(212,222,149,0.86)' : 'rgba(37,41,20,0.70)',
        valueBorder: dark ? '#D4DE95' : '#252914',
        typePalette: dark
            ? ['#d4de95','#bac095','#f8fadf','#8e9862','#fff1b8','#dceec2']
            : ['#636b2f','#252914','#bac095','#d4de95','#8a6f24','#7a3b24'],
        low: dark ? 'rgba(255,196,176,0.88)' : 'rgba(255,107,107,0.76)',
        healthy: dark ? 'rgba(186,192,149,0.88)' : 'rgba(99,107,47,0.76)'
    };
}
function axisOptions(t){
    return {
        x:{ticks:{color:t.tick,font:{size:11,weight:'600'}},grid:{color:t.grid},beginAtZero:true},
        y:{ticks:{color:t.tick,font:{size:11,weight:'600'}},grid:{color:t.grid},beginAtZero:true}
    };
}
const theme=chartTheme();
const monthChart=new Chart(document.getElementById('monthChart'),{
    type:'bar',
    data:{labels:<?= json_encode($monthLabels) ?>,datasets:[{label:'Orders',data:<?= json_encode($monthCounts) ?>,backgroundColor:theme.monthBg,borderColor:theme.monthBorder,borderWidth:1.5,borderRadius:6}]},
    options:{plugins:{legend:{display:false}},scales:{x:{ticks:{color:theme.tick,font:{size:11,weight:'600'}},grid:{color:theme.grid}},y:{ticks:{color:theme.tick,font:{size:11,weight:'600'}},grid:{color:theme.grid},beginAtZero:true}}}
});
const topChart=new Chart(document.getElementById('topChart'),{
    type:'bar',
    data:{labels:<?= json_encode($topNames) ?>,datasets:[{label:'Stock',data:<?= json_encode($topQtys) ?>,backgroundColor:theme.topBg,borderColor:theme.topBorder,borderWidth:1.5,borderRadius:6}]},
    options:{indexAxis:'y',plugins:{legend:{display:false}},scales:{x:{ticks:{color:theme.tick,font:{size:11,weight:'600'}},grid:{color:theme.grid},beginAtZero:true},y:{ticks:{color:theme.tick,font:{size:10,weight:'600'}},grid:{color:theme.grid}}}}
});
const statusChart=new Chart(document.getElementById('statusChart'),{
    type:'doughnut',
    data:{labels:['Pending','Delivered'],datasets:[{data:[<?= $pendingCnt ?>,<?= $deliveredCnt ?>],backgroundColor:[theme.pending,theme.delivered],borderColor:['#fbbf24','#16a34a'],borderWidth:1.5}]},
    options:{cutout:'68%',plugins:{legend:{labels:{color:theme.tick,font:{size:12,weight:'600'}}}}}
});
const supplierChart=new Chart(document.getElementById('supplierChart'),{
    type:'bar',
    data:{labels:<?= json_encode($supplierNames) ?>,datasets:[{label:'Orders',data:<?= json_encode($supplierCounts) ?>,backgroundColor:theme.supplierBg,borderColor:theme.supplierBorder,borderWidth:1.5,borderRadius:6}]},
    options:{plugins:{legend:{display:false}},scales:axisOptions(theme)}
});
const valueChart=new Chart(document.getElementById('valueChart'),{
    type:'bar',
    data:{labels:<?= json_encode($valueNames) ?>,datasets:[{label:'Inventory Value',data:<?= json_encode($valueTotals) ?>,backgroundColor:theme.valueBg,borderColor:theme.valueBorder,borderWidth:1.5,borderRadius:6}]},
    options:{indexAxis:'y',plugins:{legend:{display:false},tooltip:{callbacks:{label:(ctx)=>'Rs. '+Number(ctx.raw || 0).toLocaleString('en-IN')}}},scales:{x:{ticks:{color:theme.tick,font:{size:11,weight:'600'},callback:(v)=>'Rs. '+Number(v).toLocaleString('en-IN')},grid:{color:theme.grid},beginAtZero:true},y:{ticks:{color:theme.tick,font:{size:10,weight:'600'}},grid:{color:theme.grid}}}}
});
const typeChart=new Chart(document.getElementById('typeChart'),{
    type:'polarArea',
    data:{labels:<?= json_encode($typeNames) ?>,datasets:[{data:<?= json_encode($typeCounts) ?>,backgroundColor:theme.typePalette,borderColor:'#111',borderWidth:1}]},
    options:{plugins:{legend:{position:'right',labels:{color:theme.tick,font:{size:11,weight:'600'}}}},scales:{r:{ticks:{display:false},grid:{color:theme.grid}}}}
});
const stockHealthChart=new Chart(document.getElementById('stockHealthChart'),{
    type:'doughnut',
    data:{labels:['Healthy Stock','Low Stock'],datasets:[{data:[<?= (int)$healthyStock ?>,<?= (int)$lowStock ?>],backgroundColor:[theme.healthy,theme.low],borderColor:['#636b2f','#7a3b24'],borderWidth:1.5}]},
    options:{cutout:'66%',plugins:{legend:{labels:{color:theme.tick,font:{size:12,weight:'600'}}}}}
});
function updateChartsTheme(){
    const t=chartTheme();
    monthChart.data.datasets[0].backgroundColor=t.monthBg;
    monthChart.data.datasets[0].borderColor=t.monthBorder;
    topChart.data.datasets[0].backgroundColor=t.topBg;
    topChart.data.datasets[0].borderColor=t.topBorder;
    statusChart.data.datasets[0].backgroundColor=[t.pending,t.delivered];
    supplierChart.data.datasets[0].backgroundColor=t.supplierBg;
    supplierChart.data.datasets[0].borderColor=t.supplierBorder;
    valueChart.data.datasets[0].backgroundColor=t.valueBg;
    valueChart.data.datasets[0].borderColor=t.valueBorder;
    typeChart.data.datasets[0].backgroundColor=t.typePalette;
    stockHealthChart.data.datasets[0].backgroundColor=[t.healthy,t.low];
    [monthChart,topChart,supplierChart,valueChart].forEach(chart=>{
        chart.options.scales.x.ticks.color=t.tick;
        chart.options.scales.x.grid.color=t.grid;
        chart.options.scales.y.ticks.color=t.tick;
        chart.options.scales.y.grid.color=t.grid;
        chart.update();
    });
    statusChart.options.plugins.legend.labels.color=t.tick;
    typeChart.options.plugins.legend.labels.color=t.tick;
    typeChart.options.scales.r.grid.color=t.grid;
    stockHealthChart.options.plugins.legend.labels.color=t.tick;
    statusChart.update();
    typeChart.update();
    stockHealthChart.update();
}
</script>
</body>
</html>

