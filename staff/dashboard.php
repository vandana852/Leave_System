<?php
session_start();
require_once '../config.php';
if (!isset($_SESSION['user']) || $_SESSION['user']['role']!=='staff') {
    header('Location: ../index.php'); exit;
}
$conn = db_connect();
$uid = intval($_SESSION['user']['id']);
$msg='';
// attendance: check if exists for today
$today = date('Y-m-d');
$stmt = $conn->prepare("SELECT * FROM attendance WHERE user_id=? AND date=?");
$stmt->bind_param('is',$uid,$today); $stmt->execute(); $attendance = $stmt->get_result()->fetch_assoc();

// handle form submissions
if ($_SERVER['REQUEST_METHOD']==='POST') {
    if (isset($_POST['apply_leave'])) {
        $type = $_POST['type']; $start = $_POST['start_date']; $end = $_POST['end_date']; $reason = $_POST['reason'];
        $ins = $conn->prepare("INSERT INTO leaves (user_id, `type`, start_date, end_date, reason) VALUES (?,?,?,?,?)");
        $ins->bind_param('issss',$uid,$type,$start,$end,$reason); $ins->execute();
        $msg = 'Leave applied successfully';
        header('Location: dashboard.php'); exit;
    }
    if (isset($_POST['checkin']) && !$attendance) {
        $now = date('Y-m-d H:i:s');
        $ins = $conn->prepare("INSERT INTO attendance (user_id, check_in, date) VALUES (?,?,?)");
        $ins->bind_param('iss',$uid,$now,$today); $ins->execute();
        header('Location: dashboard.php'); exit;
    }
    if (isset($_POST['checkout']) && $attendance && !$attendance['check_out']) {
        $now = date('Y-m-d H:i:s');
        $up = $conn->prepare("UPDATE attendance SET check_out=? WHERE id=?");
        $up->bind_param('si',$now,$attendance['id']); $up->execute();
        header('Location: dashboard.php'); exit;
    }
}

// fetch leaves and history
$leaves = $conn->query("SELECT * FROM leaves WHERE user_id=$uid ORDER BY applied_at DESC")->fetch_all(MYSQLI_ASSOC);
$history = $conn->query("SELECT * FROM attendance WHERE user_id=$uid ORDER BY date DESC")->fetch_all(MYSQLI_ASSOC);
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Staff Dashboard</title>
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
  </style>
</head>
<body>
  <div class="sidebar">
    <div class="brand"><i class="fa fa-code"></i> &nbsp; Company</div>
    <a href="dashboard.php" class="active"><i class="fa fa-home me-2"></i> Dashboard</a>
    <a href="#myleaves"><i class="fa fa-file-alt me-2"></i> My Leaves</a>
    <a href="#attendance"><i class="fa fa-clock me-2"></i> Attendance</a>
    <a href="../logout.php"><i class="fa fa-sign-out-alt me-2"></i> Logout</a>
  </div>

  <div class="content">
    <div class="topbar d-flex justify-content-between align-items-center">
      <h4 class="mb-0">Staff Dashboard</h4>
      <div><span class="badge bg-light text-dark">Hello, <?php echo htmlspecialchars($_SESSION['user']['name']); ?></span> <a href="../logout.php" class="ms-3">Logout</a></div>
    </div>

    <div class="row g-3 mb-3">
      <div class="col-md-4"><div class="card p-3"><h6 class="small-muted">Leave Balance</h6><h3>12 Days</h3></div></div>
      <div class="col-md-4">
        <div class="card p-3">
          <h6 class="small-muted">Attendance</h6>
          <form method="post">
            <?php if(!$attendance): ?>
              <button name="checkin" class="btn btn-success">Check In</button>
            <?php elseif($attendance && !$attendance['check_out']): ?>
              <button name="checkout" class="btn btn-danger">Check Out</button>
            <?php else: ?>
              <span class="badge bg-secondary">Completed for Today</span>
            <?php endif; ?>
          </form>
        </div>
      </div>
      <div class="col-md-4"><div class="card p-3"><h6 class="small-muted">Applied Leaves</h6><h3><?php echo count($leaves); ?></h3></div></div>
    </div>

    <div id="apply" class="card p-3 mb-3">
      <h5>Apply for Leave</h5>
      <?php if($msg): ?><div class="alert alert-success"><?=$msg?></div><?php endif; ?>
      <form method="post">
        <div class="row g-2">
          <div class="col-md-3"><select name="type" required class="form-control"><option value="">Select Type</option><option>Casual Leave</option><option>Sick Leave</option><option>Earned Leave</option></select></div>
          <div class="col-md-3"><input type="date" name="start_date" required class="form-control"></div>
          <div class="col-md-3"><input type="date" name="end_date" required class="form-control"></div>
          <div class="col-md-3"><button name="apply_leave" class="btn btn-primary w-100">Apply</button></div>
        </div>
        <div class="mt-2"><textarea name="reason" class="form-control" rows="3" placeholder="Reason (optional)"></textarea></div>
      </form>
    </div>

    <div id="myleaves" class="card p-3 mb-3">
      <h5>My Leaves</h5>
      <div class="table-responsive">
      <table class="table table-bordered">
        <thead><tr><th>ID</th><th>Type</th><th>Dates</th><th>Status</th></tr></thead>
        <tbody>
        <?php foreach($leaves as $l): ?>
          <tr>
            <td><?=$l['id']?></td>
            <td><?=htmlspecialchars($l['type'])?></td>
            <td><?=htmlspecialchars($l['start_date'])?> to <?=htmlspecialchars($l['end_date'])?></td>
            <td><?=htmlspecialchars($l['status'])?></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
      </div>
    </div>

    <div id="attendance" class="card p-3 mb-3">
      <h5>My Attendance</h5>
      <div class="table-responsive">
      <table class="table table-bordered">
        <thead><tr><th>Date</th><th>Check In</th><th>Check Out</th></tr></thead>
        <tbody>
        <?php foreach($history as $h): ?>
          <tr>
            <td><?=$h['date']?></td>
            <td><?=$h['check_in']?></td>
            <td><?=$h['check_out']?: '-'?></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
      </div>
    </div>

  </div>
</body>
</html>
