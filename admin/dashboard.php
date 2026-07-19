<?php
session_start();
require_once '../config.php';
if (!isset($_SESSION['user']) || $_SESSION['user']['role']!=='admin') {
    header('Location: ../index.php'); exit;
}
$conn = db_connect();
// handle leave action
if ($_SERVER['REQUEST_METHOD']==='POST' && isset($_POST['action']) && isset($_POST['id'])) {
    $id = intval($_POST['id']);
    $action = $_POST['action']==='approve' ? 'approved' : 'rejected';
    $stmt = $conn->prepare("UPDATE leaves SET status=? WHERE id=?");
    $stmt->bind_param('si',$action,$id); $stmt->execute();
    header('Location: dashboard.php'); exit;
}
// fetch leaves and employees and attendance
$leaves = $conn->query("SELECT l.*, u.name, u.email FROM leaves l JOIN users u ON l.user_id=u.id ORDER BY l.applied_at DESC")->fetch_all(MYSQLI_ASSOC);
$users = $conn->query("SELECT id,name,email,role,created_at FROM users ORDER BY id")->fetch_all(MYSQLI_ASSOC);
$where = "";
if (isset($_GET['from']) && $_GET['from'] && isset($_GET['to']) && $_GET['to']) {
  $from = $_GET['from']; $to = $_GET['to'];
  $where = "WHERE a.date BETWEEN '".$conn->real_escape_string($from)."' AND '".$conn->real_escape_string($to)."'";
}
$attendance = $conn->query("SELECT a.*, u.name, u.email FROM attendance a JOIN users u ON a.user_id=u.id $where ORDER BY a.check_in DESC")->fetch_all(MYSQLI_ASSOC);
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Admin Dashboard</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet">
  <style>
    :root{--sidebar:#1e6aa8}
    body{background:#f1f5f9;min-height:100vh}
    .sidebar{position:fixed;left:0;top:0;height:100%;width:260px;background:var(--sidebar);color:#fff;padding-top:20px}
    .sidebar .brand{font-weight:700;text-align:center;padding:10px 0;border-bottom:1px solid rgba(255,255,255,.06)}
    .sidebar a{color:rgba(255,255,255,.9);display:block;padding:12px 18px;text-decoration:none}
    .sidebar a:hover{background:rgba(0,0,0,.08)}
    .content{margin-left:260px;padding:24px}
    .card{border-radius:10px;box-shadow:0 6px 18px rgba(16,24,40,.06)}
    .topbar{background:#fff;padding:12px 18px;border-bottom:1px solid #e6edf3;margin-bottom:18px;border-left:6px solid var(--sidebar)}
    .small-muted{color:#6b7280}
    table th, table td{vertical-align:middle}
  </style>
</head>
<body>
  <div class="sidebar">
    <div class="brand"><i class="fa fa-code"></i> &nbsp; Company</div>
    <a href="dashboard.php"><i class="fa fa-home me-2"></i> Dashboard</a>
    <a href="#employees"><i class="fa fa-users me-2"></i> Employees List</a>
    <a href="#applications"><i class="fa fa-file-alt me-2"></i> Leave Applications</a>
    <a href="#attendance"><i class="fa fa-clock me-2"></i> Attendance Records</a>
    <a href="../logout.php"><i class="fa fa-sign-out-alt me-2"></i> Logout</a>
  </div>

  <div class="content">
    <div class="topbar d-flex justify-content-between align-items-center">
      <h4 class="mb-0">Admin Dashboard</h4>
      <div><span class="badge bg-light text-dark">Hello, <?php echo htmlspecialchars($_SESSION['user']['name']); ?></span> <a href="../logout.php" class="ms-3">Logout</a></div>
    </div>

    <div class="row g-3 mb-3">
      <div class="col-md-3"><div class="card p-3"><h6 class="small-muted">Total Employees</h6><h3><?php echo count($users); ?></h3></div></div>
      <div class="col-md-3"><div class="card p-3"><h6 class="small-muted">Pending Leaves</h6><h3><?php $c=0; foreach($leaves as $l) if($l['status']=='pending') $c++; echo $c;?></h3></div></div>
      <div class="col-md-3"><div class="card p-3"><h6 class="small-muted">Today Checkins</h6><h3><?php $today=date('Y-m-d'); $tc=0; foreach($attendance as $a) if($a['date']==$today && $a['checkin_time']) $tc++; echo $tc;?></h3></div></div>
      <div class="col-md-3"><div class="card p-3"><h6 class="small-muted">Today Checkouts</h6><h3><?php $tco=0; foreach($attendance as $a) if($a['date']==$today && $a['checkout_time']) $tco++; echo $tco;?></h3></div></div>
    </div>

    <div id="applications" class="card p-3 mb-3">
      <h5>Leave Requests</h5>
      <div class="table-responsive">
      <table class="table table-bordered">
        <thead><tr><th>ID</th><th>Employee</th><th>Type</th><th>Dates</th><th>Reason</th><th>Status</th><th>Action</th></tr></thead>
        <tbody>
        <?php foreach($leaves as $l): ?>
          <tr>
            <td><?=$l['id']?></td>
            <td><?=htmlspecialchars($l['name'])?><div class="small-muted"><?=htmlspecialchars($l['email'])?></div></td>
            <td><?=htmlspecialchars($l['type'])?></td>
            <td><?=htmlspecialchars($l['start_date'])?> to <?=htmlspecialchars($l['end_date'])?></td>
            <td><?=nl2br(htmlspecialchars($l['reason']))?></td>
            <td><?=htmlspecialchars($l['status'])?></td>
            <td>
              <?php if($l['status']=='pending'): ?>
              <form method="post" style="display:inline-block">
                <input type="hidden" name="id" value="<?=$l['id']?>">
                <button name="action" value="approve" class="btn btn-sm btn-success">Approve</button>
              </form>
              <form method="post" style="display:inline-block">
                <input type="hidden" name="id" value="<?=$l['id']?>">
                <button name="action" value="reject" class="btn btn-sm btn-danger">Reject</button>
              </form>
              <?php else: ?><span class="small-muted">No actions</span><?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
      </div>
    </div>

    <div id="employees" class="card p-3 mb-3">
      <h5>Employees</h5>
      <div class="table-responsive">
      <table class="table table-bordered">
        <thead><tr><th>ID</th><th>Name</th><th>Email</th><th>Role</th><th>Joined</th></tr></thead>
        <tbody>
        <?php foreach($users as $u): ?>
          <tr>
            <td><?=$u['id']?></td>
            <td><?=htmlspecialchars($u['name'])?></td>
            <td><?=htmlspecialchars($u['email'])?></td>
            <td><?=$u['role']?></td>
            <td><?=$u['created_at']?></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
      </div>
    </div>

    
    <div id="attendance" class="card p-3 mb-3">
      <h5>Attendance Records</h5>
      <form method="get" class="row g-2 mb-2">
        <div class="col-md-3"><input type="date" name="from" class="form-control" value="<?php echo $_GET['from']??''; ?>"></div>
        <div class="col-md-3"><input type="date" name="to" class="form-control" value="<?php echo $_GET['to']??''; ?>"></div>
        <div class="col-md-3"><button class="btn btn-primary" type="submit">Filter</button></div>
      </form>
      <div class="table-responsive">
      <table class="table table-bordered">
    
      <h5>Attendance Records</h5>
      <div class="table-responsive">
      <table class="table table-bordered">
        <thead><tr><th>Date</th><th>Employee</th><th>Email</th><th>Check In</th><th>Check Out</th></tr></thead>
        <tbody>
        <?php foreach($attendance as $a): ?>
          <tr>
            <td><?=$a['date']?></td>
            <td><?=htmlspecialchars($a['name'])?></td>
            <td><?=htmlspecialchars($a['email'])?></td>
            <td><?=$a['check_in']?></td>
            <td><?=$a['check_out']?: '-'?></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
      </div>
    </div>

  </div>
</body>
</html>
