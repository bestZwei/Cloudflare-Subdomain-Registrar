<?php include 'config/config.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?php echo DOMAIN_NAME_UP; ?> Registrar</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@10"></script>
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <style>
        body {
            background-color: #f8f9fa;
        }
        .login-container {
            max-width: 400px;
            margin: 100px auto;
        }
        .card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
        }
        .card-header {
            background-color: #007bff;
            color: white;
            border-radius: 10px 10px 0 0 !important;
            padding: 20px;
        }
        .form-control {
            border-radius: 5px;
        }
        .btn-primary {
            border-radius: 5px;
            padding: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="login-container">
            <div class="card">
                <div class="card-header text-center">
                    <h4 class="mb-0"><?php echo DOMAIN_NAME_UP; ?> Registrar</h4>
                </div>
                <div class="card-body">
                    <ul class="nav nav-tabs" id="authTabs" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link active" id="login-tab" data-toggle="tab" role="tab">Login/Register</a>
                        </li>
                    </ul>
                    <div class="tab-content mt-3" id="authTabsContent">
                        <!-- Login Form -->
                        <div class="tab-pane fade show active" id="login" role="tabpanel">
                            <a href="google_login.php">
		                        <img src="/views/signinwithgoogle.png" alt="Sign in with Google" class="google-signin-btn img-fluid"> 
		                    </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html> 