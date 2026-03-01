<?php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

$config = [
  'apiKey'            => 'AIzaSyBnOd7a5v_BaOhM5sQDlRUZeX2TEZnf4nU',
  'authDomain'        => 'kaishop-id-vn.firebaseapp.com',
  'projectId'         => 'kaishop-id-vn',
  'storageBucket'     => 'kaishop-id-vn.firebasestorage.app',
  'messagingSenderId' => '1021940333448',
  'appId'             => '1:1021940333448:web:c5cc34f447b9237a24983d',
  'measurementId'     => 'G-30BCEPN0KV',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_GET['action'] ?? '') === 'verify') {
  header('Content-Type: application/json');
  $body    = json_decode(file_get_contents('php://input'), true);
  $idToken = $body['idToken'] ?? '';
  if (!$idToken) { echo json_encode(['ok'=>false,'error'=>'No idToken']); exit; }

  // Decode JWT payload (base64url) — không cần gọi Google API
  // NOTE: Đây là decode không verify signature, chỉ dùng cho dev/internal.
  // Production nên dùng firebase-php-jwt hoặc Google tokeninfo.
  function decodeJwtPayload(string $token): ?array {
    $parts = explode('.', $token);
    if (count($parts) !== 3) return null;
    $payload = $parts[1];
    // base64url → base64
    $payload = str_replace(['-','_'], ['+','/'], $payload);
    $pad     = strlen($payload) % 4;
    if ($pad) $payload .= str_repeat('=', 4 - $pad);
    $decoded = base64_decode($payload);
    if ($decoded === false) return null;
    return json_decode($decoded, true);
  }

  $payload = decodeJwtPayload($idToken);
  if (!$payload) {
    echo json_encode(['ok'=>false,'error'=>'Invalid JWT format']); exit;
  }

  // Kiểm tra token chưa hết hạn
  if (isset($payload['exp']) && $payload['exp'] < time()) {
    echo json_encode(['ok'=>false,'error'=>'Token expired']); exit;
  }

  // Kiểm tra issuer là Firebase
  $iss = $payload['iss'] ?? '';
  if (!str_starts_with($iss, 'https://securetoken.google.com/')) {
    echo json_encode(['ok'=>false,'error'=>'Invalid issuer: ' . $iss]); exit;
  }

  echo json_encode([
    'ok'   => true,
    'note' => 'decoded_locally_no_sig_verify',
    'user' => [
      'uid'     => $payload['user_id'] ?? $payload['sub'] ?? '',
      'email'   => $payload['email']   ?? '',
      'name'    => $payload['name']    ?? '',
      'picture' => $payload['picture'] ?? '',
    ],
  ]);
  exit;
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width,initial-scale=1.0"/>
  <title>Login với Google – KaiShop</title>
  <style>
    *,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
    body{min-height:100vh;display:flex;align-items:center;justify-content:center;
      background:linear-gradient(135deg,#0f0c29,#302b63,#24243e);
      font-family:'Segoe UI',sans-serif;color:#fff;flex-direction:column;gap:16px}

    .card{background:rgba(255,255,255,.07);backdrop-filter:blur(18px);
      border:1px solid rgba(255,255,255,.15);border-radius:20px;padding:48px 40px;
      width:100%;max-width:420px;text-align:center;box-shadow:0 25px 60px rgba(0,0,0,.4)}

    .logo{font-size:2rem;font-weight:800;letter-spacing:-1px;margin-bottom:6px}
    .logo span{color:#a78bfa}
    .subtitle{font-size:.9rem;color:rgba(255,255,255,.5);margin-bottom:28px}

    .env-badge{display:inline-flex;align-items:center;gap:6px;font-size:.75rem;
      padding:4px 12px;border-radius:999px;margin-bottom:20px;border:1px solid}
    .env-badge.local{background:rgba(251,191,36,.1);border-color:#fbbf24;color:#fde68a}
    .env-badge.prod {background:rgba(52,211,153,.1);border-color:#34d399;color:#a7f3d0}

    #btn-google{display:flex;align-items:center;justify-content:center;gap:12px;
      width:100%;padding:14px 20px;border:none;border-radius:12px;background:#fff;
      color:#3c4043;font-size:1rem;font-weight:600;cursor:pointer;
      transition:box-shadow .2s,transform .15s}
    #btn-google:hover:not(:disabled){box-shadow:0 6px 24px rgba(255,255,255,.25);transform:translateY(-2px)}
    #btn-google:disabled{opacity:.6;cursor:not-allowed}
    #btn-google svg{width:22px;height:22px;flex-shrink:0}

    .log-box{width:100%;max-width:420px;background:rgba(0,0,0,.4);
      border:1px solid rgba(255,255,255,.1);border-radius:12px;padding:14px 16px;
      font-family:monospace;font-size:.78rem;max-height:200px;overflow-y:auto;text-align:left}
    .log-box .line{padding:2px 0;border-bottom:1px solid rgba(255,255,255,.05)}
    .log-box .line:last-child{border-bottom:none}
    .log-box .ok {color:#34d399}
    .log-box .err{color:#f87171}
    .log-box .inf{color:#93c5fd}
    .log-box .wrn{color:#fbbf24}

    #user-section{display:none}
    .avatar{width:80px;height:80px;border-radius:50%;border:3px solid #a78bfa;
      margin:0 auto 16px;object-fit:cover}
    .user-name{font-size:1.3rem;font-weight:700;margin-bottom:4px}
    .user-email{font-size:.85rem;color:rgba(255,255,255,.5);margin-bottom:12px}
    .uid-badge{display:inline-block;background:rgba(167,139,250,.2);border:1px solid #a78bfa;
      color:#c4b5fd;border-radius:999px;padding:4px 14px;font-size:.75rem;
      word-break:break-all;margin-bottom:24px}
    #btn-logout{width:100%;padding:12px;border-radius:12px;border:1px solid rgba(255,255,255,.2);
      background:transparent;color:#fff;font-size:.95rem;cursor:pointer;transition:background .2s}
    #btn-logout:hover{background:rgba(255,255,255,.1)}

    @keyframes spin{to{transform:rotate(360deg)}}
    .spinner{width:16px;height:16px;border:2px solid rgba(60,64,67,.3);border-top-color:#3c4043;
      border-radius:50%;animation:spin .7s linear infinite;display:inline-block}
  </style>
</head>
<body>

<div class="card">
  <div class="logo">Kai<span>Shop</span></div>
  <p class="subtitle">Đăng nhập để tiếp tục</p>

  <div id="login-section">
    <div class="env-badge local" id="env-badge">⚡ Detecting…</div><br><br>
    <button id="btn-google">
      <svg viewBox="0 0 48 48" xmlns="http://www.w3.org/2000/svg">
        <path fill="#EA4335" d="M24 9.5c3.54 0 6.71 1.22 9.21 3.6l6.85-6.85C35.9 2.38 30.47 0 24 0 14.62 0 6.51 5.38 2.56 13.22l7.98 6.19C12.43 13.72 17.74 9.5 24 9.5z"/>
        <path fill="#4285F4" d="M46.98 24.55c0-1.57-.15-3.09-.38-4.55H24v9.02h12.94c-.58 2.96-2.26 5.48-4.78 7.18l7.73 6c4.51-4.18 7.09-10.36 7.09-17.65z"/>
        <path fill="#FBBC05" d="M10.53 28.59c-.48-1.45-.76-2.99-.76-4.59s.27-3.14.76-4.59l-7.98-6.19C.92 16.46 0 20.12 0 24c0 3.88.92 7.54 2.56 10.78l7.97-6.19z"/>
        <path fill="#34A853" d="M24 48c6.48 0 11.93-2.13 15.89-5.81l-7.73-6c-2.18 1.48-4.97 2.29-8.16 2.29-6.26 0-11.57-4.22-13.47-9.91l-7.98 6.19C6.51 42.62 14.62 48 24 48z"/>
      </svg>
      Đăng nhập bằng Google
    </button>
  </div>

  <div id="user-section">
    <img class="avatar" id="user-avatar" src="" alt="avatar"/>
    <div class="user-name"  id="user-name"></div>
    <div class="user-email" id="user-email"></div>
    <div class="uid-badge"  id="user-uid"></div>
    <button id="btn-logout">Đăng xuất</button>
  </div>
</div>

<div class="log-box" id="log-box">
  <div class="line inf">📋 Khởi động…</div>
</div>

<script src="https://www.gstatic.com/firebasejs/10.12.2/firebase-app-compat.js"></script>
<script src="https://www.gstatic.com/firebasejs/10.12.2/firebase-auth-compat.js"></script>
<script>
const logBox = document.getElementById('log-box');
function log(msg, type='inf') {
  console.log(`[${type.toUpperCase()}] ${msg}`);
  const d = document.createElement('div');
  d.className = `line ${type}`;
  d.textContent = `[${new Date().toLocaleTimeString()}] ${msg}`;
  logBox.appendChild(d);
  logBox.scrollTop = logBox.scrollHeight;
}

firebase.initializeApp({
  apiKey:            "<?= $config['apiKey'] ?>",
  authDomain:        "<?= $config['authDomain'] ?>",
  projectId:         "<?= $config['projectId'] ?>",
  storageBucket:     "<?= $config['storageBucket'] ?>",
  messagingSenderId: "<?= $config['messagingSenderId'] ?>",
  appId:             "<?= $config['appId'] ?>",
  measurementId:     "<?= $config['measurementId'] ?>",
});
const auth     = firebase.auth();
const provider = new firebase.auth.GoogleAuthProvider();
log('✅ Firebase initialized', 'ok');

// ── Luôn dùng Redirect (không popup) ────────────────────────────
const envBadge = document.getElementById('env-badge');
envBadge.className   = 'env-badge prod';
envBadge.textContent = '➡️ Redirect mode';
log(`Host: ${location.hostname} → REDIRECT mode`, 'inf');

// ── Auth state listener ──────────────────────────────────────────
auth.onAuthStateChanged(user => {
  if (user) {
    log(`👤 Logged in: ${user.displayName} <${user.email}>`, 'ok');
    renderUser(user);
  } else {
    log('👤 Not logged in', 'wrn');
    document.getElementById('login-section').style.display = 'block';
    document.getElementById('user-section').style.display  = 'none';
  }
});

// ── Lấy kết quả sau redirect ─────────────────────────────────────
log('🔄 Checking getRedirectResult…', 'inf');
auth.getRedirectResult().then(async result => {
  if (!result || !result.user) {
    log('ℹ️ No redirect result', 'inf');
    return;
  }
  log(`✅ Redirect OK: ${result.user.email}`, 'ok');
  await verifyToken(result.user);
}).catch(e => {
  log(`❌ Redirect error: [${e.code}] ${e.message}`, 'err');
});

// ── Button ───────────────────────────────────────────────────────
const btnGoogle = document.getElementById('btn-google');
const googleSVG = btnGoogle.innerHTML;

btnGoogle.addEventListener('click', async () => {
  btnGoogle.disabled = true;
  btnGoogle.innerHTML = '<span class="spinner"></span>&nbsp; Đang chuyển trang…';
  log('🖱️ Click! signInWithRedirect…', 'inf');
  try {
    await auth.signInWithRedirect(provider);
  } catch (e) {
    log(`❌ [${e.code}] ${e.message}`, 'err');
    btnGoogle.disabled = false;
    btnGoogle.innerHTML = googleSVG;
  }
});

// ── Verify token server-side ─────────────────────────────────────
async function verifyToken(user) {
  try {
    log('🔐 Getting idToken…', 'inf');
    const idToken = await user.getIdToken();
    log('📡 POST ?action=verify to PHP…', 'inf');
    const res  = await fetch('?action=verify', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ idToken }),
    });
    const data = await res.json();
    if (data.ok) {
      log(`🎉 PHP verify OK! name="${data.user.name}" uid=${data.user.uid}`, 'ok');
    } else {
      log(`⚠️ PHP verify failed: ${data.error}`, 'wrn');
    }
  } catch (e) {
    log(`⚠️ verifyToken exception: ${e.message}`, 'wrn');
  }
}

// ── Render user ───────────────────────────────────────────────────
function renderUser(user) {
  document.getElementById('user-avatar').src  = user.photoURL || `https://ui-avatars.com/api/?name=${encodeURIComponent(user.displayName||'U')}&background=a78bfa&color=fff`;
  document.getElementById('user-name').textContent  = user.displayName || '(no name)';
  document.getElementById('user-email').textContent = user.email || '';
  document.getElementById('user-uid').textContent   = 'UID: ' + user.uid;
  document.getElementById('login-section').style.display = 'none';
  document.getElementById('user-section').style.display  = 'block';
}

// ── Logout ────────────────────────────────────────────────────────
document.getElementById('btn-logout').addEventListener('click', () => {
  log('👋 Signing out…', 'wrn');
  auth.signOut().then(() => log('✅ Signed out', 'ok'));
});
</script>
</body>
</html>