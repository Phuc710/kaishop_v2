<?php
// Prevent direct access
if (!defined('BASE_URL')) {
    die('Direct access not permitted');
}

// If already logged in, redirect
if (isset($_SESSION['session']) && !empty($_SESSION['session'])) {
    header('Location: ' . BASE_URL . '/');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <base href="../../../" />
    <?php require __DIR__ . '/../../hethong/head2.php'; ?>
    <title>Login | <?= $siteConfig['ten_web'] ?? 'KaiShop' ?></title>
</head>
<body>
    <?php require __DIR__ . '/../../hethong/nav.php'; ?>
    
    <main>
        <section class="py-5 bg-offWhite">
            <div class="container">
                <div class="rounded-3">
                    <div class="row">
                        <div class="col-lg-6 p-3 p-lg-5 m-auto">
                            <div class="login-userset">
                                <div class="login-card">
                                    <div class="login-heading">
                                        <h3>Login to Your Account</h3>
                                        <p>Enter your credentials to access your account</p>
                                    </div>
                                    
                                    <div class="form-wrap form-focus">
                                        <span class="form-icon">
                                            <i class="feather-mail"></i>
                                        </span>
                                        <input type="text" id="username" class="form-control floating" autocomplete="username">
                                        <label class="focus-label">Username</label>
                                    </div>
                                    
                                    <div class="form-wrap form-focus pass-group">
                                        <span class="form-icon">
                                            <i class="toggle-password feather-eye-off"></i>
                                        </span>
                                        <input type="password" id="password" class="pass-input form-control floating" autocomplete="current-password">
                                        <label class="focus-label">Password</label>
                                    </div>
                                    
                                    <div class="d-flex align-items-center justify-content-between">
                                        <div class="form-wrap">
                                            <label class="custom_check mb-0">Remember me
                                                <input type="checkbox" id="remember" name="remember">
                                                <span class="checkmark"></span>
                                            </label>
                                        </div>
                                        <div class="form-wrap text-md-end">
                                            <a href="<?= BASE_URL ?>/password-reset" class="forgot-link">Forgot password?</a>
                                        </div>
                                    </div>
                                    
                                    <button type="button" onclick="login()" class="btn btn-primary w-100">
                                        <span id="button1" class="indicator-label">Login</span>
                                        <span id="button2" class="indicator-progress" style="display: none;">
                                            <i class="fa fa-spinner fa-spin"></i> Processing...
                                        </span>
                                    </button>
                                    
                                    <div class="acc-in">
                                        <p>Don't have an account?
                                            <a href="<?= BASE_URL ?>/register">Create Account</a>
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </main>
    
    <script>
        function login() {
            const button1 = document.getElementById("button1");
            const button2 = document.getElementById("button2");
            
            if (button2.disabled) return;
            
            button1.style.display = "none";
            button2.style.display = "inline-block";
            button2.disabled = true;
            
            const username = document.getElementById("username").value;
            const password = document.getElementById("password").value;
            
            fetch('<?= BASE_URL ?>/login', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'username=' + encodeURIComponent(username) + '&password=' + encodeURIComponent(password)
            })
            .then(response => response.json())
            .then(data => {
                button1.style.display = "inline-block";
                button2.style.display = "none";
                button2.disabled = false;
                
                if (data.success) {
                    showMessage(data.message || "Login successful!", "success");
                    setTimeout(() => {
                        window.location.href = '<?= BASE_URL ?>/';
                    }, 1000);
                } else {
                    showMessage(data.message || "Login failed", "error");
                }
            })
            .catch(error => {
                button1.style.display = "inline-block";
                button2.style.display = "none";
                button2.disabled = false;
                showMessage("Connection error!", "error");
            });
        }
        
        // Allow Enter key to submit
        document.getElementById("password").addEventListener("keypress", function(event) {
            if (event.key === "Enter") {
                login();
            }
        });
    </script>
    
    <?php require __DIR__ . '/../../hethong/foot.php'; ?>
</body>
</html>
