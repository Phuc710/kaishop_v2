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
    <title>Register | <?= $siteConfig['ten_web'] ?? 'KaiShop' ?></title>
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
                                        <h3>Create New Account</h3>
                                        <p>Fill in the details to create your account</p>
                                    </div>
                                    
                                    <div class="form-wrap form-focus">
                                        <span class="form-icon">
                                            <i class="feather-user"></i>
                                        </span>
                                        <input type="text" id="username" class="form-control floating" autocomplete="username">
                                        <label class="focus-label">Username</label>
                                    </div>
                                    
                                    <div class="form-wrap form-focus">
                                        <span class="form-icon">
                                            <i class="feather-mail"></i>
                                        </span>
                                        <input type="email" id="email" class="form-control floating" autocomplete="email">
                                        <label class="focus-label">Email</label>
                                    </div>
                                    
                                    <div class="form-wrap form-focus pass-group">
                                        <span class="form-icon">
                                            <i class="toggle-password feather-eye-off"></i>
                                        </span>
                                        <input type="password" id="password" class="pass-input form-control floating" autocomplete="new-password">
                                        <label class="focus-label">Password</label>
                                    </div>
                                    
                                    <button type="button" onclick="register()" class="btn btn-primary w-100">
                                        <span id="button1" class="indicator-label">Create Account</span>
                                        <span id="button2" class="indicator-progress" style="display: none;">
                                            <i class="fa fa-spinner fa-spin"></i> Processing...
                                        </span>
                                    </button>
                                    
                                    <div class="acc-in">
                                        <p>Already have an account?
                                            <a href="<?= BASE_URL ?>/login">Login</a>
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
        function register() {
            const button1 = document.getElementById("button1");
            const button2 = document.getElementById("button2");
            
            if (button2.disabled) return;
            
            button1.style.display = "none";
            button2.style.display = "inline-block";
            button2.disabled = true;
            
            const username = document.getElementById("username").value;
            const email = document.getElementById("email").value;
            const password = document.getElementById("password").value;
            
            fetch('<?= BASE_URL ?>/register', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'username=' + encodeURIComponent(username) + 
                      '&email=' + encodeURIComponent(email) +
                      '&password=' + encodeURIComponent(password)
            })
            .then(response => response.json())
            .then(data => {
                button1.style.display = "inline-block";
                button2.style.display = "none";
                button2.disabled = false;
                
                if (data.success) {
                    showMessage(data.message || "Account created successfully!", "success");
                    setTimeout(() => {
                        window.location.href = '<?= BASE_URL ?>/';
                    }, 1000);
                } else {
                    showMessage(data.message || "Registration failed", "error");
                }
            })
            .catch(error => {
                button1.style.display = "inline-block";
                button2.style.display = "none";
                button2.disabled = false;
                showMessage("Connection error!", "error");
            });
        }
    </script>
    
    <?php require __DIR__ . '/../../hethong/foot.php'; ?>
</body>
</html>
