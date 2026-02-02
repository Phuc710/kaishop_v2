<?php

/**
 * Admin Service Controller
 */
class ServiceController extends Controller {
    private $authService;
    
    public function __construct() {
        $this->authService = new AuthService();
    }
    
    /**
     * Check admin access
     */
    private function requireAdmin() {
        $this->authService->requireAuth();
        $user = $this->authService->getCurrentUser();
        
        if (!isset($user['level']) || $user['level'] != 9) {
            http_response_code(403);
            die('Access denied - Admin only');
        }
    }
    
    // ========== SOURCE CODE (KHO CODE) ==========
    
    /**
     * List source codes
     */
    public function codes() {
        $this->requireAdmin();
        global $connection, $chungapi;
        
        // Delete logic
        if (isset($_GET['delete'])) {
            $delete = $_GET['delete'];
            $result = $connection->query("DELETE FROM `khocode` WHERE `id` = '$delete'");
            if ($result) {
                $_SESSION['notify'] = ['type' => 'success', 'title' => 'Thành Công', 'message' => 'Xóa thành công'];
            } else {
                $_SESSION['notify'] = ['type' => 'error', 'title' => 'Lỗi', 'message' => 'Lỗi máy chủ'];
            }
            $this->redirect(url('admin/services/codes'));
        }
        
        $codes = $connection->query("SELECT * FROM `khocode` ORDER BY id DESC")->fetch_all(MYSQLI_ASSOC);
        
        $this->view('admin/services/codes/index', [
            'codes' => $codes,
            'chungapi' => $chungapi
        ]);
    }
    
    /**
     * Form to add code
     */
    public function addCode() {
        $this->requireAdmin();
        global $chungapi;
        
        $this->view('admin/services/codes/add', [
            'chungapi' => $chungapi
        ]);
    }
    
    /**
     * Process add code
     */
    public function storeCode() {
        $this->requireAdmin();
        global $connection;
        
        $title = $this->post('title');
        $noidung = $this->post('noidung');
        $gia = $this->post('gia');
        $link = $this->post('link');
        $buy = $this->post('buy');
        $list_img = $this->post('list_img');
        $status = $this->post('status');
        $img = $this->post('img');

        $title = $connection->real_escape_string($title);
        $noidung = $connection->real_escape_string($noidung);
        $link = $connection->real_escape_string($link);
        $list_img = $connection->real_escape_string($list_img);
        $img = $connection->real_escape_string($img);
        
        $sql = "INSERT INTO `khocode` (`title`, `noidung`, `gia`, `link`, `buy`, `list_img`, `status`, `img`) 
                VALUES ('$title', '$noidung', '$gia', '$link', '$buy', '$list_img', '$status', '$img')";
        
        if ($connection->query($sql)) {
            $_SESSION['notify'] = ['type' => 'success', 'title' => 'Thành Công', 'message' => 'Thêm code thành công'];
            $this->redirect(url('admin/services/codes'));
        } else {
            $_SESSION['notify'] = ['type' => 'error', 'title' => 'Lỗi', 'message' => 'Lỗi máy chủ: ' . $connection->error];
            $this->redirect(url('admin/services/codes/add'));
        }
    }
    
    /**
     * Form to edit code
     */
    public function editCode($id) {
        $this->requireAdmin();
        global $chungapi, $connection;
        
        $code = $connection->query("SELECT * FROM `khocode` WHERE `id` = '$id'")->fetch_assoc();
        
        if (!$code) {
            $_SESSION['notify'] = ['type' => 'error', 'title' => 'Lỗi', 'message' => 'Code không tồn tại'];
            $this->redirect(url('admin/services/codes'));
        }
        
        $this->view('admin/services/services/codes/edit', [
            'chungapi' => $chungapi,
            'code' => $code
        ]);
    }
    
    /**
     * Process update code
     */
    public function updateCode($id) {
        $this->requireAdmin();
        global $connection;
        
        $title = $this->post('title');
        $noidung = $this->post('noidung');
        $gia = $this->post('gia');
        $link = $this->post('link');
        $buy = $this->post('buy');
        $list_img = $this->post('list_img');
        $status = $this->post('status');
        $img = $this->post('img');

        $title = $connection->real_escape_string($title);
        $noidung = $connection->real_escape_string($noidung);
        $link = $connection->real_escape_string($link);
        $list_img = $connection->real_escape_string($list_img);
        $img = $connection->real_escape_string($img);
        
        $sql = "UPDATE `khocode` SET 
                `title` = '$title', 
                `noidung` = '$noidung', 
                `gia` = '$gia', 
                `link` = '$link', 
                `buy` = '$buy', 
                `list_img` = '$list_img', 
                `status` = '$status', 
                `img` = '$img' 
                WHERE `id` = '$id'";
        
        if ($connection->query($sql)) {
            $_SESSION['notify'] = ['type' => 'success', 'title' => 'Thành Công', 'message' => 'Cập nhật thành công'];
            $this->redirect(url('admin/services/codes'));
        } else {
            $_SESSION['notify'] = ['type' => 'error', 'title' => 'Lỗi', 'message' => 'Lỗi máy chủ: ' . $connection->error];
        }
    }
    
    // ========== LOGO MANAGEMENT ==========
    
    /**
     * List logos
     */
    public function logos() {
        $this->requireAdmin();
        global $connection, $chungapi;
        
        // Delete logic
        if (isset($_GET['delete'])) {
            $delete = $_GET['delete'];
            $result = $connection->query("DELETE FROM `khologo` WHERE `id` = '$delete'");
            if ($result) {
                $_SESSION['notify'] = ['type' => 'success', 'title' => 'Thành Công', 'message' => 'Xóa thành công'];
            } else {
                $_SESSION['notify'] = ['type' => 'error', 'title' => 'Lỗi', 'message' => 'Lỗi máy chủ'];
            }
            $this->redirect(url('admin/services/logos'));
        }
        
        $logos = $connection->query("SELECT * FROM `khologo` ORDER BY id DESC")->fetch_all(MYSQLI_ASSOC);
        
        $this->view('admin/services/logos/index', [
            'logos' => $logos,
            'chungapi' => $chungapi
        ]);
    }
    
    /**
     * Form to add logo
     */
    public function addLogo() {
        $this->requireAdmin();
        global $chungapi;
        
        $this->view('admin/services/logos/add', [
            'chungapi' => $chungapi
        ]);
    }
    
    /**
     * Process add logo
     */
    public function storeLogo() {
        $this->requireAdmin();
        global $connection;
        
        $title = $this->post('title');
        $gia = $this->post('gia');
        $list_img = $this->post('list_img');
        $status = $this->post('status');
        $img = $this->post('img');

        $title = $connection->real_escape_string($title);
        $list_img = $connection->real_escape_string($list_img);
        $img = $connection->real_escape_string($img);
        
        $sql = "INSERT INTO `khologo` (`title`, `gia`, `list_img`, `status`, `img`) 
                VALUES ('$title', '$gia', '$list_img', '$status', '$img')";
        
        if ($connection->query($sql)) {
            $_SESSION['notify'] = ['type' => 'success', 'title' => 'Thành Công', 'message' => 'Thêm logo thành công'];
            $this->redirect(url('admin/services/logos'));
        } else {
            $_SESSION['notify'] = ['type' => 'error', 'title' => 'Lỗi', 'message' => 'Lỗi máy chủ: ' . $connection->error];
            $this->redirect(url('admin/services/logos/add'));
        }
    }
    
    /**
     * Form to edit logo
     */
    public function editLogo($id) {
        $this->requireAdmin();
        global $chungapi, $connection;
        
        $logo = $connection->query("SELECT * FROM `khologo` WHERE `id` = '$id'")->fetch_assoc();
        
        if (!$logo) {
            $_SESSION['notify'] = ['type' => 'error', 'title' => 'Lỗi', 'message' => 'Logo không tồn tại'];
            $this->redirect(url('admin/services/logos'));
        }
        
        $this->view('admin/services/logos/edit', [
            'chungapi' => $chungapi,
            'logo' => $logo
        ]);
    }
    
    /**
     * Process update logo
     */
    public function updateLogo($id) {
        $this->requireAdmin();
        global $connection;
        
        $title = $this->post('title');
        $gia = $this->post('gia');
        $list_img = $this->post('list_img');
        $status = $this->post('status');
        $img = $this->post('img');

        $title = $connection->real_escape_string($title);
        $list_img = $connection->real_escape_string($list_img);
        $img = $connection->real_escape_string($img);
        
        $sql = "UPDATE `khologo` SET 
                `title` = '$title', 
                `gia` = '$gia', 
                `list_img` = '$list_img', 
                `status` = '$status', 
                `img` = '$img' 
                WHERE `id` = '$id'";
        
        if ($connection->query($sql)) {
            $_SESSION['notify'] = ['type' => 'success', 'title' => 'Thành Công', 'message' => 'Cập nhật thành công'];
            $this->redirect(url('admin/services/logos'));
        } else {
            $_SESSION['notify'] = ['type' => 'error', 'title' => 'Lỗi', 'message' => 'Lỗi máy chủ: ' . $connection->error];
            $this->redirect(url('admin/services/logos/edit/' . $id));
        }
    }
    
    // ========== DOMAIN MANAGEMENT ==========
    
    /**
     * List domains
     */
    public function domains() {
        $this->requireAdmin();
        global $connection, $chungapi;
        
        // Delete logic
        if (isset($_GET['delete'])) {
            $delete = $_GET['delete'];
            $result = $connection->query("DELETE FROM `ds_domain` WHERE `id` = '$delete'");
            if ($result) {
                $_SESSION['notify'] = ['type' => 'success', 'title' => 'Thành Công', 'message' => 'Xóa thành công'];
            } else {
                $_SESSION['notify'] = ['type' => 'error', 'title' => 'Lỗi', 'message' => 'Lỗi máy chủ'];
            }
            $this->redirect(url('admin/services/domains'));
        }
        
        $domains = $connection->query("SELECT * FROM `ds_domain` ORDER BY id DESC")->fetch_all(MYSQLI_ASSOC);
        
        $this->view('admin/services/domains/index', [
            'domains' => $domains,
            'chungapi' => $chungapi
        ]);
    }
    
    /**
     * Form to add domain
     */
    public function addDomain() {
        $this->requireAdmin();
        global $chungapi;
        
        $this->view('admin/services/domains/add', [
            'chungapi' => $chungapi
        ]);
    }
    
    /**
     * Process add domain
     */
    public function storeDomain() {
        $this->requireAdmin();
        global $connection;
        
        $duoi_mien = $this->post('duoi_mien');
        $gia = $this->post('gia');
        $giahan = $this->post('giahan');
        $status = $this->post('status');

        $duoi_mien = $connection->real_escape_string($duoi_mien);
        
        $sql = "INSERT INTO `ds_domain` (`duoi_mien`, `gia`, `giahan`, `status`) 
                VALUES ('$duoi_mien', '$gia', '$giahan', '$status')";
        
        if ($connection->query($sql)) {
            $_SESSION['notify'] = ['type' => 'success', 'title' => 'Thành Công', 'message' => 'Thêm domain thành công'];
            $this->redirect(url('admin/services/domains'));
        } else {
            $_SESSION['notify'] = ['type' => 'error', 'title' => 'Lỗi', 'message' => 'Lỗi máy chủ: ' . $connection->error];
            $this->redirect(url('admin/services/domains/add'));
        }
    }
    
    /**
     * Form to edit domain
     */
    public function editDomain($id) {
        $this->requireAdmin();
        global $chungapi, $connection;
        
        $domain = $connection->query("SELECT * FROM `ds_domain` WHERE `id` = '$id'")->fetch_assoc();
        
        if (!$domain) {
            $_SESSION['notify'] = ['type' => 'error', 'title' => 'Lỗi', 'message' => 'Domain không tồn tại'];
            $this->redirect(url('admin/services/domains'));
        }
        
        $this->view('admin/services/domains/edit', [
            'chungapi' => $chungapi,
            'domain' => $domain
        ]);
    }
    
    /**
     * Process update domain
     */
    public function updateDomain($id) {
        $this->requireAdmin();
        global $connection;
        
        $duoi_mien = $this->post('duoi_mien');
        $gia = $this->post('gia');
        $giahan = $this->post('giahan');
        $status = $this->post('status');

        $duoi_mien = $connection->real_escape_string($duoi_mien);
        
        $sql = "UPDATE `ds_domain` SET 
                `duoi_mien` = '$duoi_mien', 
                `gia` = '$gia', 
                `giahan` = '$giahan', 
                `status` = '$status' 
                WHERE `id` = '$id'";
        
        if ($connection->query($sql)) {
            $_SESSION['notify'] = ['type' => 'success', 'title' => 'Thành Công', 'message' => 'Cập nhật thành công'];
            $this->redirect(url('admin/services/domains'));
        } else {
            $_SESSION['notify'] = ['type' => 'error', 'title' => 'Lỗi', 'message' => 'Lỗi máy chủ: ' . $connection->error];
        }
    }
    
    // ========== HOSTING PACKAGE MANAGEMENT ==========
    
    /**
     * List hosting packs
     */
    public function hostPacks() {
        $this->requireAdmin();
        global $connection, $chungapi;
        
        // Delete logic
        if (isset($_GET['delete'])) {
            $delete = $_GET['delete'];
            $result = $connection->query("DELETE FROM `list_host` WHERE `id` = '$delete'");
            if ($result) {
                $_SESSION['notify'] = ['type' => 'success', 'title' => 'Thành Công', 'message' => 'Xóa thành công'];
            } else {
                $_SESSION['notify'] = ['type' => 'error', 'title' => 'Lỗi', 'message' => 'Lỗi máy chủ'];
            }
            $this->redirect(url('admin/services/hosting/packs'));
        }
        
        $packs = $connection->query("SELECT * FROM `list_host` ORDER BY id DESC")->fetch_all(MYSQLI_ASSOC);
        
        $this->view('admin/services/hosting/packs/index', [
            'packs' => $packs,
            'chungapi' => $chungapi
        ]);
    }
    
    /**
     * Form to add hosting pack
     */
    public function addHostPack() {
        $this->requireAdmin();
        global $chungapi, $connection;
        
        $servers = $connection->query("SELECT * FROM `list_server_host` WHERE `status` = 'ON'")->fetch_all(MYSQLI_ASSOC);
        
        $this->view('admin/services/hosting/packs/add', [
            'chungapi' => $chungapi,
            'servers' => $servers
        ]);
    }
    
    /**
     * Process add hosting pack
     */
    public function storeHostPack() {
        $this->requireAdmin();
        global $connection;
        
        $name_host = $this->post('name_host');
        $title_host = $this->post('title_host');
        $server_host = $this->post('server_host');
        $code = $this->post('code');
        $gia_host = $this->post('gia_host');
        $dung_luong = $this->post('dung_luong');
        $mien_khac = $this->post('mien_khac');
        $firewall = $this->post('firewall');
        $bi_danh = $this->post('bi_danh');

        $name_host = $connection->real_escape_string($name_host);
        $code = $connection->real_escape_string($code);
        $dung_luong = $connection->real_escape_string($dung_luong);
        
        $sql = "INSERT INTO `list_host` (`name_host`, `title_host`, `server_host`, `code`, `gia_host`, `dung_luong`, `mien_khac`, `firewall`, `bi_danh`) 
                VALUES ('$name_host', '$title_host', '$server_host', '$code', '$gia_host', '$dung_luong', '$mien_khac', '$firewall', '$bi_danh')";
        
        if ($connection->query($sql)) {
            $_SESSION['notify'] = ['type' => 'success', 'title' => 'Thành Công', 'message' => 'Thêm gói thành công'];
            $this->redirect(url('admin/services/hosting/packs'));
        } else {
            $_SESSION['notify'] = ['type' => 'error', 'title' => 'Lỗi', 'message' => 'Lỗi máy chủ: ' . $connection->error];
            $this->redirect(url('admin/services/hosting/packs/add'));
        }
    }
    
    /**
     * Form to edit hosting pack
     */
    public function editHostPack($id) {
        $this->requireAdmin();
        global $chungapi, $connection;
        
        $pack = $connection->query("SELECT * FROM `list_host` WHERE `id` = '$id'")->fetch_assoc();
        if (!$pack) {
            $_SESSION['notify'] = ['type' => 'error', 'title' => 'Lỗi', 'message' => 'Gói không tồn tại'];
            $this->redirect(url('admin/services/hosting/packs'));
        }
        
        $servers = $connection->query("SELECT * FROM `list_server_host` WHERE `status` = 'ON'")->fetch_all(MYSQLI_ASSOC);
        
        $this->view('admin/services/hosting/packs/edit', [
            'chungapi' => $chungapi,
            'pack' => $pack,
            'servers' => $servers
        ]);
    }
    
    /**
     * Process update hosting pack
     */
    public function updateHostPack($id) {
        $this->requireAdmin();
        global $connection;
        
        $name_host = $this->post('name_host');
        $title_host = $this->post('title_host');
        $server_host = $this->post('server_host');
        $code = $this->post('code');
        $gia_host = $this->post('gia_host');
        $dung_luong = $this->post('dung_luong');
        $mien_khac = $this->post('mien_khac');
        $firewall = $this->post('firewall');
        $bi_danh = $this->post('bi_danh');

        $name_host = $connection->real_escape_string($name_host);
        $code = $connection->real_escape_string($code);
        $dung_luong = $connection->real_escape_string($dung_luong);
        
        $sql = "UPDATE `list_host` SET 
                `name_host` = '$name_host', 
                `title_host` = '$title_host', 
                `server_host` = '$server_host', 
                `code` = '$code', 
                `gia_host` = '$gia_host', 
                `dung_luong` = '$dung_luong', 
                `mien_khac` = '$mien_khac', 
                `firewall` = '$firewall', 
                `bi_danh` = '$bi_danh' 
                WHERE `id` = '$id'";
        
        if ($connection->query($sql)) {
            $_SESSION['notify'] = ['type' => 'success', 'title' => 'Thành Công', 'message' => 'Cập nhật thành công'];
            $this->redirect(url('admin/services/hosting/packs'));
        } else {
            $_SESSION['notify'] = ['type' => 'error', 'title' => 'Lỗi', 'message' => 'Lỗi máy chủ: ' . $connection->error];
            $this->redirect(url('admin/services/hosting/packs/edit/' . $id));
        }
    }

    // ========== HOSTING SERVER MANAGEMENT ==========
    
    /**
     * List hosting servers
     */
    public function hostServers() {
        $this->requireAdmin();
        global $connection, $chungapi;
        
        // Delete logic
        if (isset($_GET['delete'])) {
            $delete = $_GET['delete'];
            $result = $connection->query("DELETE FROM `list_server_host` WHERE `id` = '$delete'");
            if ($result) {
                $_SESSION['notify'] = ['type' => 'success', 'title' => 'Thành Công', 'message' => 'Xóa thành công'];
            } else {
                $_SESSION['notify'] = ['type' => 'error', 'title' => 'Lỗi', 'message' => 'Lỗi máy chủ'];
            }
            $this->redirect(url('admin/services/hosting/servers'));
        }
        
        $servers = $connection->query("SELECT * FROM `list_server_host` ORDER BY id DESC")->fetch_all(MYSQLI_ASSOC);
        
        $this->view('admin/services/hosting/servers/index', [
            'servers' => $servers,
            'chungapi' => $chungapi
        ]);
    }
    
    /**
     * Form to add hosting server
     */
    public function addHostServer() {
        $this->requireAdmin();
        global $chungapi;
        
        $this->view('admin/services/hosting/servers/add', [
            'chungapi' => $chungapi
        ]);
    }
    
    /**
     * Process add hosting server
     */
    public function storeHostServer() {
        $this->requireAdmin();
        global $connection;
        
        $name_server = $this->post('name_server');
        $link_login = $this->post('link_login');
        $tk_whm = $this->post('tk_whm');
        $mk_whm = $this->post('mk_whm');
        $ip_whm = $this->post('ip_whm');
        $ns1 = $this->post('ns1');
        $ns2 = $this->post('ns2');
        $status = $this->post('status');

        $name_server = $connection->real_escape_string($name_server);
        
        $sql = "INSERT INTO `list_server_host` (`name_server`, `link_login`, `tk_whm`, `mk_whm`, `ip_whm`, `ns1`, `ns2`, `status`) 
                VALUES ('$name_server', '$link_login', '$tk_whm', '$mk_whm', '$ip_whm', '$ns1', '$ns2', '$status')";
        
        if ($connection->query($sql)) {
            $_SESSION['notify'] = ['type' => 'success', 'title' => 'Thành Công', 'message' => 'Thêm server thành công'];
            $this->redirect(url('admin/services/hosting/servers'));
        } else {
            $_SESSION['notify'] = ['type' => 'error', 'title' => 'Lỗi', 'message' => 'Lỗi máy chủ: ' . $connection->error];
            $this->redirect(url('admin/services/hosting/servers/add'));
        }
    }
    
    /**
     * Form to edit hosting server
     */
    public function editHostServer($id) {
        $this->requireAdmin();
        global $chungapi, $connection;
        
        $server = $connection->query("SELECT * FROM `list_server_host` WHERE `id` = '$id'")->fetch_assoc();
        if (!$server) {
            $_SESSION['notify'] = ['type' => 'error', 'title' => 'Lỗi', 'message' => 'Server không tồn tại'];
            $this->redirect(url('admin/services/hosting/servers'));
        }
        
        $this->view('admin/services/hosting/servers/edit', [
            'chungapi' => $chungapi,
            'server' => $server
        ]);
    }
    
    /**
     * Process update hosting server
     */
    public function updateHostServer($id) {
        $this->requireAdmin();
        global $connection;
        
        $name_server = $this->post('name_server');
        $link_login = $this->post('link_login');
        $tk_whm = $this->post('tk_whm');
        $mk_whm = $this->post('mk_whm');
        $ip_whm = $this->post('ip_whm');
        $ns1 = $this->post('ns1');
        $ns2 = $this->post('ns2');
        $status = $this->post('status');

        $name_server = $connection->real_escape_string($name_server);
        
        $sql = "UPDATE `list_server_host` SET 
                `name_server` = '$name_server', 
                `link_login` = '$link_login', 
                `tk_whm` = '$tk_whm', 
                `mk_whm` = '$mk_whm', 
                `ip_whm` = '$ip_whm', 
                `ns1` = '$ns1', 
                `ns2` = '$ns2', 
                `status` = '$status' 
                WHERE `id` = '$id'";
        
        if ($connection->query($sql)) {
            $_SESSION['notify'] = ['type' => 'success', 'title' => 'Thành Công', 'message' => 'Cập nhật thành công'];
            $this->redirect(url('admin/services/hosting/servers'));
        } else {
            $_SESSION['notify'] = ['type' => 'error', 'title' => 'Lỗi', 'message' => 'Lỗi máy chủ: ' . $connection->error];
            $this->redirect(url('admin/services/hosting/servers/edit/' . $id));
        }
    }
}
