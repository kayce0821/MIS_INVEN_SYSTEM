<?php
session_start();
include '../INCLUDES/database.php';

// 1. Handle the actual verification when the user clicks the button (POST)
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['token'])) {
    $token = $_POST['token'];

    $stmt = $mysql->prepare("SELECT user_id, full_name, role FROM user WHERE verification_token = ? AND status = 'Pending' LIMIT 1");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        $user_id = $user['user_id'];
        $full_name = $user['full_name'];
        $role = $user['role'];

        // Activate the account and erase the one-time token
        $update = $mysql->prepare("UPDATE user SET status = 'Active', verification_token = NULL WHERE user_id = ?");
        $update->bind_param("i", $user_id);
        $update->execute();

        // Automatically log the student in
        $_SESSION['user_id'] = $user_id;
        $_SESSION['full_name'] = $full_name;
        $_SESSION['role'] = $role;

        // Show success message
        echo "<!DOCTYPE html><html><head><script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script><link href='https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback' rel='stylesheet'><style>body { background-color: #f8f9fa; font-family: 'Source Sans Pro', sans-serif; }</style></head><body><script>
            document.addEventListener('DOMContentLoaded', function() {
                Swal.fire({
                    title: 'Email Verified!',
                    text: 'Your account is now active. Welcome to EquipTrack!',
                    icon: 'success',
                    confirmButtonColor: '#198754',
                    confirmButtonText: 'Go to Dashboard'
                }).then(() => {
                    window.location.href = '../PAGES/studentDashboard.php';
                });
            });
        </script></body></html>";
        exit();
    } else {
        // Token invalid during POST
        echo "<!DOCTYPE html><html><head><script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script></head><body style='background-color: #f8f9fa;'><script>
            document.addEventListener('DOMContentLoaded', function() {
                Swal.fire({
                    title: 'Link Expired',
                    text: 'This verification link is invalid or has already been used.',
                    icon: 'error',
                    confirmButtonColor: '#dc3545',
                    confirmButtonText: 'Go to Login'
                }).then(() => {
                    window.location.href = '../PAGES/login.php';
                });
            });
        </script></body></html>";
        exit();
    }
}

// 2. Handle the initial link click from the email (GET)
if (isset($_GET['token'])) {
    $token = $_GET['token'];

    // Check if the token is valid so we can show the button
    $stmt = $mysql->prepare("SELECT user_id FROM user WHERE verification_token = ? AND status = 'Pending' LIMIT 1");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        // Show a UI with a button to stop bots from auto-verifying
        ?>
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Verify Account | EquipTrack</title>
            <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        </head>
        <body class="bg-light d-flex align-items-center justify-content-center vh-100">
            <div class="card p-5 text-center shadow-lg" style="max-width: 450px; border-radius: 1rem;">
                <h2 class="text-success fw-bold mb-3">Almost there!</h2>
                <p class="text-muted mb-4">Click the button below to confirm your email address and activate your EquipTrack account.</p>
                <form method="POST" action="">
                    <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
                    <button type="submit" class="btn btn-success btn-lg w-100 fw-bold">Activate My Account</button>
                </form>
            </div>
        </body>
        </html>
        <?php
    } else {
        // Token is already invalid/expired before they even clicked the button
        echo "<!DOCTYPE html><html><head><script src='https://cdn.jsdelivr.net/npm/sweetalert2@11'></script></head><body style='background-color: #f8f9fa;'><script>
            document.addEventListener('DOMContentLoaded', function() {
                Swal.fire({
                    title: 'Link Expired',
                    text: 'This verification link is invalid or has already been used.',
                    icon: 'error',
                    confirmButtonColor: '#dc3545',
                    confirmButtonText: 'Go to Login'
                }).then(() => {
                    window.location.href = '../PAGES/login.php';
                });
            });
        </script></body></html>";
    }
} else {
    // No token provided at all
    header("Location: ../PAGES/login.php");
    exit();
}
?>