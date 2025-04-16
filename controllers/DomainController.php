<?php
session_start();
require_once __DIR__ . '/../includes/cloudflare.php';
require_once __DIR__ . '/../includes/db.php';

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

if (!isset($_SESSION['user'])) {
    echo json_encode(['success' => false, 'message' => 'User not authenticated']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'list_domains') {
    listUserDomains();
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'get_subdomain_info') {
    getSubdomainInfo();
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'list_all_domains') {
    listAllDomains();
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'list_banned_subdomains') {
    listBannedSubdomains();
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'];
    $subdomain = filter_input(INPUT_POST, 'subdomain', FILTER_SANITIZE_SPECIAL_CHARS);
    $NSServer = filter_input(INPUT_POST, 'nsserver', FILTER_SANITIZE_SPECIAL_CHARS);

    if ($action === 'register_domain') {
	    $data = array(
            'secret' => HCAPTCHA_SECRET,
            'response' => $_POST['h-captcha-response']
        );
		$verify = curl_init();
		curl_setopt($verify, CURLOPT_URL, "https://hcaptcha.com/siteverify");
		curl_setopt($verify, CURLOPT_POST, true);
		curl_setopt($verify, CURLOPT_POSTFIELDS, http_build_query($data));
		curl_setopt($verify, CURLOPT_RETURNTRANSFER, true);
		$response = curl_exec($verify);
		$responseData = json_decode($response);
		if(!$responseData->success) {
			echo json_encode(['success' => false, 'message' => $l_captchaerr]);
            exit;
		}
        if (empty($subdomain)) {
            echo json_encode(['success' => false, 'message' => $l_subdomainerr]);
            exit;
        }
        if (empty($NSServer)) {
            echo json_encode(['success' => false, 'message' => $_nsrecorderr]);
            exit;
        }
        $validationResult = validateSubdomain($subdomain);
        if (!$validationResult['success']) {
            echo json_encode($validationResult);
            exit;
        }
        $validationResult = validateNSServer($NSServer);
        if (!$validationResult['success']) {
            echo json_encode($validationResult);
            exit;
        }
        if (checkSubdomainExists(CLOUDFLARE_ZONE_ID, $subdomain)) {
            echo json_encode(['success' => false, 'message' => $l_subdomainexerr]);
            exit;
        }
        if (!canRegisterMoreSubdomains()) {
            echo json_encode(['success' => false, 'message' => $l_sublimiterr]);
            exit;
        }
        registerDomain(CLOUDFLARE_ZONE_ID, $subdomain, $NSServer);
    } elseif ($action === 'delete_domain') {
        deleteDomain(CLOUDFLARE_ZONE_ID, $subdomain);
    } elseif ($action === 'admin_delete_domain') {
        adminDeleteDomain(CLOUDFLARE_ZONE_ID, $subdomain);
    } elseif ($action === 'update_domain_limit') {
        $userId = filter_input(INPUT_POST, 'userId', FILTER_VALIDATE_INT);
        $newLimit = filter_input(INPUT_POST, 'newLimit', FILTER_VALIDATE_INT);

        if ($userId === false || $newLimit === false) {
            echo json_encode(['success' => false, 'message' => $l_invalidinput]);
            exit;
        }

        updateDomainLimit($userId, $newLimit);
    } elseif ($action === 'add_banned_subdomain') {
        if (empty($subdomain)) {
            echo json_encode(['success' => false, 'message' => $l_subdomainerr]);
            exit;
        }
        addBannedSubdomain($subdomain);
    } elseif ($action === 'delete_banned_subdomain') {
        $subdomainId = filter_input(INPUT_POST, 'subdomainId', FILTER_VALIDATE_INT);
        if ($subdomainId === false) {
            echo json_encode(['success' => false, 'message' => $l_invalidinput]);
            exit;
        }
        deleteBannedSubdomain($subdomainId);
    }
    exit;
}

function canRegisterMoreSubdomains() {
    global $conn;
    $userId = $_SESSION['user']['id'];
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM domains WHERE user_id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $stmt->bind_result($count);
    $stmt->fetch();
    $stmt->close();

    $stmt = $conn->prepare("SELECT subdomain_limit FROM users WHERE id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $stmt->bind_result($limit);
    $stmt->fetch();
    $stmt->close();

    return $count < $limit;
}

function getSubdomainInfo() {
    global $conn;
    $userId = $_SESSION['user']['id'];
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM domains WHERE user_id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $stmt->bind_result($count);
    $stmt->fetch();
    $stmt->close();

    $stmt = $conn->prepare("SELECT subdomain_limit FROM users WHERE id = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $stmt->bind_result($limit);
    $stmt->fetch();
    $stmt->close();

    echo json_encode(['success' => true, 'count' => $count, 'limit' => $limit]);
}

function listUserDomains() {
    global $conn;
    $userId = $_SESSION['user']['id'];
    $stmt = $conn->prepare("SELECT subdomain, nsserver, created_at FROM domains WHERE user_id = ? ORDER BY created_at DESC");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $domains = [];
    while ($row = $result->fetch_assoc()) {
        $row['full_domain'] = $row['subdomain'] . "." . DOMAIN_NAME;
        //$row['ip_address'] = ALLOWED_IP;
        $row['ip_address'] = "HIDDEN";
        $domains[] = $row;
    }
    
    echo json_encode(['success' => true, 'domains' => $domains]);
}

function checkSubdomainExists($zoneId, $subdomain) {
    $totalCount = getARecord($zoneId, $subdomain);
    return $totalCount > 0;
}

function isSubdomainBanned($subdomain) {
    global $conn;
    $stmt = $conn->prepare("SELECT COUNT(*) FROM banned_subdomains WHERE subdomain = ?");
    $stmt->bind_param("s", $subdomain);
    $stmt->execute();
    $stmt->bind_result($count);
    $stmt->fetch();
    $stmt->close();
    return $count > 0;
}

function registerDomain($zoneId, $subdomain, $NSServer) {
	global $l_subdomainbanned, $l_subdomainexist, $l_registeredsuccess, $l_faileddb;
    // Check if the subdomain is banned
    if (isSubdomainBanned($subdomain)) {
        echo json_encode(['success' => false, 'message' => $l_subdomainbanned]);
        return;
    }

    // Check if the DNS record already exists
    if (checkSubdomainExists($zoneId, $subdomain)) {
        echo json_encode(['success' => false, 'message' => $l_subdomainexist]);
        return;
    }

    $result = createNSRecord($zoneId, $subdomain, $NSServer);
    if ($result['success']) {
        if (saveDomainToDatabase($subdomain, $NSServer)) {
            echo json_encode(['success' => true, 'message' => $l_registeredsuccess]);
        } else {
            echo json_encode(['success' => false, 'message' => $l_faileddb]);
        }
    } else {
        echo json_encode(['success' => false, 'message' => $result['message']]);
    }
}

function saveDomainToDatabase($subdomain, $NSServer) {
    global $conn;
    $userId = $_SESSION['user']['id'];
    $stmt = $conn->prepare("INSERT INTO domains (user_id, subdomain, nsserver) VALUES (?, ?, ?)");
    $stmt->bind_param("iss", $userId, $subdomain, $NSServer);
    return $stmt->execute();
}

function deleteDomain($zoneId, $subdomain) {
    global $conn, $l_unauthorized, $l_deleted, $l_faileddb;
    $userId = $_SESSION['user']['id'];

    // Check if the domain belongs to the user
    if (!doesDomainBelongToUser($subdomain, $userId)) {
        echo json_encode(['success' => false, 'message' => $l_unauthorized]);
        return;
    }

    $result = deleteARecord($zoneId, $subdomain);
    if ($result['success']) {
        if (removeDomainFromDatabase($subdomain)) {
            echo json_encode(['success' => true, 'message' => $l_deleted]);
        } else {
            echo json_encode(['success' => false, 'message' => $l_faileddb]);
        }
    } else {
        echo json_encode(['success' => false, 'message' => $result['message']]);
    }
}

function removeDomainFromDatabase($subdomain) {
    global $conn;
    $userId = $_SESSION['user']['id'];
    $stmt = $conn->prepare("DELETE FROM domains WHERE user_id = ? AND subdomain = ?");
    $stmt->bind_param("is", $userId, $subdomain);
    return $stmt->execute();
}

// Function to check if the current user is an admin
function isAdmin() {
    return isset($_SESSION['user']) && $_SESSION['user']['role'] === 'admin';
}

// Function to check if a domain belongs to a user
function doesDomainBelongToUser($subdomain, $userId) {
    global $conn;
    $stmt = $conn->prepare("SELECT COUNT(*) FROM domains WHERE subdomain = ? AND user_id = ?");
    $stmt->bind_param("si", $subdomain, $userId);
    $stmt->execute();
    $stmt->bind_result($count);
    $stmt->fetch();
    $stmt->close();
    return $count > 0;
}

// Function to add a banned subdomain
function addBannedSubdomain($subdomain) {
	global $l_subalreadybanned, $l_subdomainbannedsuccess, $l_bansubfail;
    if (!isAdmin()) {
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        return;
    }
    global $conn;

    // Check if the subdomain is already banned
    $stmt = $conn->prepare("SELECT COUNT(*) FROM banned_subdomains WHERE subdomain = ?");
    $stmt->bind_param("s", $subdomain);
    $stmt->execute();
    $stmt->bind_result($count);
    $stmt->fetch();
    $stmt->close();

    if ($count > 0) {
        echo json_encode(['success' => false, 'message' => $l_subalreadybanned]);
        return;
    }

    // Insert the new banned subdomain
    $stmt = $conn->prepare("INSERT INTO banned_subdomains (subdomain) VALUES (?)");
    $stmt->bind_param("s", $subdomain);
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => $l_subdomainbannedsuccess]);
    } else {
        echo json_encode(['success' => false, 'message' => $l_bansubfail]);
    }
    $stmt->close();
}

// Function to delete a domain
function adminDeleteDomain($zoneId, $subdomain) {
	global $l_deleted, $l_faileddb;
    if (!isAdmin()) {
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        return;
    }

    // Delete the DNS record from Cloudflare
    $result = deleteARecord($zoneId, $subdomain);
    if (!$result['success']) {
        echo json_encode(['success' => false, 'message' => $l_cferrdel . $result['message']]);
        return;
    }

    // Delete the domain from the database
    global $conn;
    $stmt = $conn->prepare("DELETE FROM domains WHERE subdomain = ?");
    $stmt->bind_param("s", $subdomain);
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => $l_deleted]);
    } else {
        echo json_encode(['success' => false, 'message' => $l_faileddb]);
    }
    $stmt->close();
}

// Function to update the allowed domain number for a user
function updateDomainLimit($userId, $newLimit) {
	global $l_updatedsuccessfullimit, $l_faileddb;
    if (!isAdmin()) {
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        return;
    }
    global $conn;
    $stmt = $conn->prepare("UPDATE users SET subdomain_limit = ? WHERE id = ?");
    $stmt->bind_param("ii", $newLimit, $userId);
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => $l_updatedsuccessfullimit]);
    } else {
        echo json_encode(['success' => false, 'message' => $l_faileddb]);
    }
    $stmt->close();
}

function listAllDomains() {
    if (!isAdmin()) {
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        return;
    }
    global $conn;
    $stmt = $conn->prepare("SELECT subdomain, user_id, created_at, nsserver FROM domains ORDER BY created_at DESC");
    $stmt->execute();
    $result = $stmt->get_result();
    
    $domains = [];
    while ($row = $result->fetch_assoc()) {
        $domains[] = $row;
    }
    
    echo json_encode(['success' => true, 'domains' => $domains]);
}

function listBannedSubdomains() {
    if (!isAdmin()) {
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        return;
    }
    global $conn;
    $stmt = $conn->prepare("SELECT id, subdomain FROM banned_subdomains ORDER BY created_at DESC");
    $stmt->execute();
    $result = $stmt->get_result();
    
    $bannedSubdomains = [];
    while ($row = $result->fetch_assoc()) {
        $bannedSubdomains[] = $row;
    }
    
    echo json_encode(['success' => true, 'bannedSubdomains' => $bannedSubdomains]);
}

function deleteBannedSubdomain($subdomainId) {
	global $l_bannedsubdelete, $l_faileddb;
    if (!isAdmin()) {
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        return;
    }
    global $conn;
    $stmt = $conn->prepare("DELETE FROM banned_subdomains WHERE id = ?");
    $stmt->bind_param("i", $subdomainId);
    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => $l_bannedsubdelete]);
    } else {
        echo json_encode(['success' => false, 'message' => $l_faileddb]);
    }
    $stmt->close();
}

function validateSubdomain($subdomain) {
	global $l_invalidsubdm;
    $subdomainPattern = '/^(?!-)[A-Za-z0-9-]{3,63}(?<!-)$/';
    if (preg_match($subdomainPattern, $subdomain)) {
        return ['success' => true];
    } else {
        return ['success' => false, 'message' => $l_invalidsubdm];
    }
}

function validateNSServer($NSServer) {
	global $l_invalidnss;
    $NSServerPattern = '/^(((?!-))(xn--|_)?[a-z0-9-]{0,61}[a-z0-9]{1,1}\.)*(xn--)?([a-z0-9][a-z0-9\-]{0,60}|[a-z0-9-]{1,30}\.[a-z]{2,})$/';
    if (preg_match($NSServerPattern, $NSServer)) {
        return ['success' => true];
    } else {
        return ['success' => false, 'message' => $l_invalidnss];
    }
} 

echo json_encode(['success' => false, 'message' => 'Unknown Error']);