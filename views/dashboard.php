<?php
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: ../index.php');
    exit();
}

// Correct location for configuration file inclusion
require_once '../config/config.php';

include "../lang/function.php";

// Language
if($_SESSION['language']){
}else{
	$lang = substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 4); 
    if(preg_match("/zh-c/i", $lang)){
	    $_SESSION["language"] = "zh";
    }elseif(preg_match("/en/i", $lang)){
		$_SESSION["language"] = "en";
    }else{
	    $_SESSION["language"] = 'en';
    }
}
$language_name = getLanguageName($_SESSION["language"]);
include "../lang/lang/".$language_name.".php";

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $l_dashboard; ?></title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@10"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <script src='https://www.hCaptcha.com/1/api.js' async defer></script>
    <style>
        .navbar {
            margin-bottom: 2rem;
        }
        .domain-list {
            margin-top: 2rem;
        }
    </style>
</head>
<body>
	<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
	    <div class="container">
	        <a class="navbar-brand" href="#"><?php echo DOMAIN_NAME_UP; ?> <?php echo $l_registrar; ?></a>
	        <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav">
	            <span class="navbar-toggler-icon"></span>
	        </button>
	        <div class="collapse navbar-collapse" id="navbarNav">
	            <ul class="navbar-nav ml-auto">
	                <?php if (isset($_SESSION['user']) && $_SESSION['user']['role'] === 'admin'): ?>
	                <li class="nav-item">
	                    <a class="nav-link" href="domain_administrator.php"><?php echo $l_admin_panel; ?></a>
	                </li>
	                <?php endif; ?>
	                <li class="nav-item">
	                    <a class="nav-link"><?php echo $_SESSION['user']['username'];?></a>
	                </li>
	                <li class="nav-item dropdown">
	                    <a class="nav-link dropdown-toggle" href="#" id="languageDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
	                        <i class="fas fa-globe"></i>
	                    </a>
	                    <div class="dropdown-menu dropdown-menu-right" aria-labelledby="languageDropdown">
	                        <a class="dropdown-item" href="/lang/langswitch.php?url=views/dashboard.php&language=en">English</a>
	                        <a class="dropdown-item" href="/lang/langswitch.php?url=views/dashboard.php&language=zh">中文</a>
	                    </div>
	                </li>
	                <li class="nav-item">
	                    <a class="nav-link" href="logout.php"><?php echo $l_logout; ?></a>
	                </li>
	            </ul>
	        </div>
	    </div>
	</nav>


    <div class="container">
        <div class="row">
            <div class="col-md-6 offset-md-3">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0"><?php echo $l_register_new_domain; ?></h4>
                    </div>
                    <div class="card-body">
                        <p><?php echo $l_youhaveregistered; ?> <b><span id="subdomainCount"></span></b> <?php echo $l_outof; ?> <b><span id="subdomainLimit"></span></b> <?php echo $l_domains; ?></p>
                        <form action="../controllers/DomainController.php" method="post" id="domainForm">
                            <input type="hidden" name="action" value="register_domain">
                            <div class="form-group">
                                <label for="subdomain"><?php echo $l_subdomain; ?>:</label>
                                <div class="input-group">
                                    <input type="text" id="subdomain" name="subdomain" class="form-control" required>
                                    <div class="input-group-append">
                                        <span class="input-group-text"><?php echo DOMAIN_NAME; ?></span>
                                    </div>
                                </div>
                                <label for="subdomain"><?php echo $l_nsrecord; ?>:</label>
                                <div class="input-group">
                                    <input type="text" id="nsserver" name="nsserver" class="form-control" required>
                                </div>
                            </div>
                            <div class="h-captcha" data-sitekey="<?php echo HCAPTCHA_SITE_KEY; ?>"></div>
                            <button type="submit" class="btn btn-primary btn-block">
                                <i class="fas fa-plus"></i> <?php echo $l_registerdomain; ?>
                            </button>
                        </form>
                    </div>
                </div>

                <div class="domain-list">
                    <div class="card">
                        <div class="card-header bg-primary text-white">
                            <h4 class="mb-0"><?php echo $l_yourdomains; ?></h4>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th><?php echo $l_domain; ?></th>
                                            <th><?php echo $l_nsrecord; ?></th>
                                            <th><?php echo $l_action; ?></th>
                                        </tr>
                                    </thead>
                                    <tbody id="domainsList">
                                        <!-- Domains will be loaded here -->
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.4/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script>
        $(document).ready(function() {
            $('#domainForm').on('submit', function(e) {
			    e.preventDefault();
			
			    const token = $('[name="h-captcha-response"]').val();
			
			    if (!token) {
			        //Swal.fire('Error', 'Please complete the captcha.', 'error');
			        hcaptcha.execute();
			        return;
			    }
			
			    Swal.fire({
			        title: '<?php echo $l_registering; ?>...',
			        text: '<?php echo $l_wait_reg_domain; ?>',
			        allowOutsideClick: false,
			        didOpen: () => {
			            Swal.showLoading();
			        }
			    });
			
			    $.post($(this).attr('action'), $(this).serialize(), function(response) {
			        try {
			            const result = typeof response === 'string' ? JSON.parse(response) : response;
			            if (result.success) {
			                Swal.fire('Success', result.message, 'success').then(() => {
			                    loadDomains();
			                    loadSubdomainInfo();
			                    $('#subdomain').val('');
			                    $('#nsserver').val('');
			                    hcaptcha.reset(); // reset captcha after success
			                });
			            } else {
			                Swal.fire('Error', result.message, 'error');
			                hcaptcha.reset();
			            }
			        } catch (error) {
			            Swal.fire('Error', 'An unexpected error occurred', 'error');
			            console.error('Error:', error);
			            hcaptcha.reset();
			        }
			    }).fail(function(xhr, status, error) {
			        Swal.fire('Error', 'Failed to connect to the server', 'error');
			        console.error('Server Error:', error);
			        hcaptcha.reset();
			    });
			});



            function loadDomains() {
                $.get('../controllers/DomainController.php?action=list_domains', function(response) {
                    try {
                        const result = typeof response === 'string' ? JSON.parse(response) : response;
                        if (result.success) {
                            let html = '';
                            result.domains.forEach(function(domain) {
                                html += `
                                    <tr>
                                        <td>${domain.full_domain}</td>
                                        <td>${domain.nsserver}</td>
                                        <td>
                                            <button class="btn btn-danger btn-sm delete-domain" data-subdomain="${domain.subdomain}">
                                                <?php echo $l_delete; ?>
                                            </button>
                                        </td>
                                    </tr>
                                `;
                            });
                            $('#domainsList').html(html);
                        }
                    } catch (error) {
                        console.error('Error loading domains:', error);
                    }
                });
            }

            function loadSubdomainInfo() {
                $.get('../controllers/DomainController.php?action=get_subdomain_info', function(response) {
                    const result = JSON.parse(response);
                    if (result.success) {
                        $('#subdomainCount').text(result.count);
                        $('#subdomainLimit').text(result.limit);
                    }
                });
            }

            // Load domains when page loads
            loadDomains();

            // Load subdomain count and limit
            loadSubdomainInfo();

            $(document).on('click', '.delete-domain', function() {
                const subdomain = $(this).data('subdomain');
                Swal.fire({
                    title: '<?php echo $l_rusure; ?>?',
                    text: "<?php echo $l_norevert; ?>!",
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: '<?php echo $l_yesdelete; ?>!'
                }).then((result) => {
                    if (result.isConfirmed) {
                        Swal.fire({
                            title: '<?php echo $l_deleting; ?>...',
                            text: '<?php echo $l_waitdelete; ?>.',
                            allowOutsideClick: false,
                            didOpen: () => {
                                Swal.showLoading();
                            }
                        });
                        $.post('../controllers/DomainController.php', { action: 'delete_domain', subdomain: subdomain }, function(response) {
                            const result = JSON.parse(response);
                            if (result.success) {
                                Swal.fire('<?php echo $l_deleted; ?>!', result.message, 'success').then(() => {
                                    loadDomains();
                                    loadSubdomainInfo();
                                });
                            } else {
                                Swal.fire('Error!', result.message, 'error');
                            }
                        });
                    }
                });
            });
        });
    </script>
</body>
</html> 