<?php
session_start();

// Check if the user is an admin
if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'admin') {
    header('Location: ../index.php');
    exit();
}
require_once '../config/config.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@10"></script>
</head>
<body>
	<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="/"><?php echo DOMAIN_NAME_UP; ?> Registrar</a>
            <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ml-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="logout.php">Logout</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
    <div class="container mt-5">
        <h1 class="text-center">Admin Dashboard</h1>

        <!-- List of Users -->
        <div class="card mb-4">
            <div class="card-header">Users</div>
            <div class="card-body">
                <table class="table">
                    <thead>
                        <tr>
                            <th>User ID</th>
                            <th>Username</th>
                            <th>Role</th>
                            <th>Domain Limit</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="userList">
                        <!-- Users will be loaded here -->
                    </tbody>
                </table>
            </div>
        </div>

        <!-- List of Domains -->
        <div class="card mb-4">
            <div class="card-header">Domains</div>
            <div class="card-body">
                <table class="table">
                    <thead>
                        <tr>
	                        <th>User ID</th>
                            <th>Subdomain</th>
                            <th>NS Server</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody id="domainList">
                        <!-- Domains will be loaded here -->
                    </tbody>
                </table>
            </div>
        </div>
        
        <!-- Add Banned Subdomain -->
        <div class="card mb-4">
            <div class="card-header">Add Banned Subdomain</div>
            <div class="card-body">
                <form id="banSubdomainForm">
                    <div class="form-group">
                        <label for="bannedSubdomain">Subdomain:</label>
                        <input type="text" id="bannedSubdomain" name="bannedSubdomain" class="form-control" required>
                    </div>
                    <button type="submit" class="btn btn-danger">Ban Subdomain</button>
                </form>
            </div>
        </div>

        <!-- List of Banned Subdomains -->
        <div class="card mb-4">
            <div class="card-header">Banned Subdomains</div>
            <div class="card-body">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Subdomain</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody id="bannedSubdomainList">
                        <!-- Banned subdomains will be loaded here -->
                    </tbody>
                </table>
            </div>
        </div>
        
    </div>

    <script>
        $(document).ready(function() {
            // Handle ban subdomain form submission
            $('#banSubdomainForm').on('submit', function(e) {
                e.preventDefault();
                const subdomain = $('#bannedSubdomain').val();
                $.post('../controllers/DomainController.php', { action: 'add_banned_subdomain', subdomain: subdomain }, function(response) {
                    const result = JSON.parse(response);
                    Swal.fire(result.success ? 'Success' : 'Error', result.message, result.success ? 'success' : 'error').then(() => {
                        if (result.success) loadBannedSubdomains();
                    });
                });
            });

            // Load users
            function loadUsers() {
			    $.get('../controllers/UserController.php?action=list_users', function(response) {
			        const result = JSON.parse(response);
			        if (result.success) {
			            let html = '';
			            result.users.forEach(function(user) {
			                html += `
			                    <tr>
			                        <td>${user.id}</td>
			                        <td>${user.username}</td>
			                        <td>${user.role}</td>
			                        <td>${user.subdomain_limit}</td>
			                        <td>
			                            <button class="btn btn-primary btn-sm update-limit" data-user-id="${user.id}">Update Limit</button>
			                            ${user.role !== 'admin' ? `<button class="btn btn-danger btn-sm delete-user" data-user-id="${user.id}">Delete</button>` : ''}
			                        </td>
			                    </tr>
			                `;
			            });
			            $('#userList').html(html);
			        }
			    });
			}

            // Handle delete user
            $(document).on('click', '.delete-user', function() {
                const userId = $(this).data('user-id');
                $.post('../controllers/UserController.php', { action: 'delete_user', userId: userId }, function(response) {
                    const result = JSON.parse(response);
                    Swal.fire(result.success ? 'Success' : 'Error', result.message, result.success ? 'success' : 'error').then(() => {
                        if (result.success) loadUsers();
                    });
                });
            });

            // Handle update domain limit
            $(document).on('click', '.update-limit', function() {
                const userId = $(this).data('user-id');
                Swal.fire({
                    title: 'Update Domain Limit',
                    input: 'number',
                    inputLabel: 'New Domain Limit',
                    inputPlaceholder: 'Enter new domain limit',
                    showCancelButton: true,
                    inputValidator: (value) => {
                        if (!value) {
                            return 'You need to enter a number!';
                        }
                    }
                }).then((result) => {
                    if (result.isConfirmed) {
                        const newLimit = result.value;
                        $.post('../controllers/DomainController.php', { action: 'update_domain_limit', userId: userId, newLimit: newLimit }, function(response) {
                            const result = JSON.parse(response);
                            Swal.fire(result.success ? 'Success' : 'Error', result.message, result.success ? 'success' : 'error').then(() => {
                                if (result.success) loadUsers();
                            });
                        });
                    }
                });
            });

            // Load banned subdomains
            function loadBannedSubdomains() {
                $.get('../controllers/DomainController.php?action=list_banned_subdomains', function(response) {
                    const result = JSON.parse(response);
                    if (result.success) {
                        let html = '';
                        result.bannedSubdomains.forEach(function(subdomain) {
                            html += `
                                <tr>
                                    <td>${subdomain.subdomain}</td>
                                    <td>
                                        <button class="btn btn-danger btn-sm delete-banned-subdomain" data-subdomain-id="${subdomain.id}">Delete</button>
                                    </td>
                                </tr>
                            `;
                        });
                        $('#bannedSubdomainList').html(html);
                    }
                });
            }

            // Handle delete banned subdomain
            $(document).on('click', '.delete-banned-subdomain', function() {
                const subdomainId = $(this).data('subdomain-id');
                $.post('../controllers/DomainController.php', { action: 'delete_banned_subdomain', subdomainId: subdomainId }, function(response) {
                    const result = JSON.parse(response);
                    Swal.fire(result.success ? 'Success' : 'Error', result.message, result.success ? 'success' : 'error').then(() => {
                        if (result.success) loadBannedSubdomains();
                    });
                });
            });

            // Load all domains for admin
            function loadAllDomains() {
                $.get('../controllers/DomainController.php?action=list_all_domains', function(response) {
                    const result = JSON.parse(response);
                    if (result.success) {
                        let html = '';
                        result.domains.forEach(function(domain) {
                            html += `
                                <tr>
                                	<td>${domain.user_id}</td>
                                    <td>${domain.subdomain}</td>
                                    <td>${domain.nsserver}</td>
                                    <td>
                                        <button class="btn btn-danger btn-sm delete-domain" data-subdomain="${domain.subdomain}">Delete</button>
                                    </td>
                                </tr>
                            `;
                        });
                        $('#domainList').html(html);
                    }
                });
            }

            // Handle delete domain
            $(document).on('click', '.delete-domain', function() {
                const subdomain = $(this).data('subdomain');
                $.post('../controllers/DomainController.php', { action: 'admin_delete_domain', subdomain: subdomain }, function(response) {
                    const result = JSON.parse(response);
                    Swal.fire(result.success ? 'Success' : 'Error', result.message, result.success ? 'success' : 'error').then(() => {
                        if (result.success) loadAllDomains();
                    });
                });
            });

            // Initial load
            loadUsers();
            loadBannedSubdomains();
            loadAllDomains();
        });
    </script>
</body>
</html>