<?php
session_start();
require_once 'config.php';
$conn = db_connect();
$err='';
if ($_SERVER['REQUEST_METHOD']==='POST') {
    $email = $_POST['email'] ?? '';
    $password = md5($_POST['password'] ?? '');
    $stmt = $conn->prepare("SELECT id,name,role FROM users WHERE email=? AND password=?");
    $stmt->bind_param('ss',$email,$password);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($user = $res->fetch_assoc()) {
        $_SESSION['user'] = $user;
        if ($user['role']==='admin') header('Location: admin/dashboard.php');
        else header('Location: staff/dashboard.php');
        exit;
    } else {
        $err = 'Invalid credentials';
    }
}
?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Login - Leave & Attendance</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    body{background:#f4f7fb;display:flex;align-items:center;justify-content:center;height:100vh}
    .card{width:420px;border-radius:12px;box-shadow:0 6px 18px rgba(0,0,0,.06)}
  </style>
</head>
<body>
  <div class="card p-4">
    <h4 class="mb-3">Leave & Attendance System</h4>
    <?php if($err): ?><div class="alert alert-danger"><?=$err?></div><?php endif; ?>
    <form method="post">
      <div class="mb-2"><input required name="email" class="form-control" placeholder="Email" type="email"></div>
      <div class="mb-3"><input required name="password" class="form-control" placeholder="Password" type="password"></div>
      <button class="btn btn-primary w-100" type="submit">Login</button>
    </form>
    <hr>
    <div class="small text-muted">Default admin: admin@example.com / admin123</div>
    <div class="small text-muted">Default staff: staff1@example.com / staff123</div>
  </div>
</body>
</html>
