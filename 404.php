<?php
require 'config.php';
require 'helpers.php';

http_response_code(404);
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=5">
<meta name="theme-color" content="#1a5276">
<link rel="stylesheet" href="/assets/style.css">
<title>Halaman Tidak Ditemukan - SKD CAT-BKN</title>
<style>
.error-404 {
    text-align: center;
    padding: 4rem 1rem;
    min-height: 60vh;
    display: flex;
    flex-direction: column;
    justify-content: center;
    align-items: center;
}

.error-404 .code {
    font-size: 8rem;
    font-weight: 700;
    color: #1a5276;
    line-height: 1;
    margin-bottom: 1rem;
    text-shadow: 2px 2px 4px rgba(0,0,0,0.1);
}

.error-404 .message {
    font-size: 1.5rem;
    color: #555;
    margin-bottom: 2rem;
}

.error-404 .description {
    font-size: 1rem;
    color: #777;
    margin-bottom: 2rem;
    max-width: 500px;
}

.error-404 .actions {
    display: flex;
    gap: 1rem;
    flex-wrap: wrap;
    justify-content: center;
}

.error-404 .btn {
    display: inline-flex;
    align-items: center;
    padding: 0.75rem 1.5rem;
    border-radius: 6px;
    text-decoration: none;
    font-weight: 600;
    transition: all 0.3s ease;
}

.error-404 .btn-primary {
    background: #1a5276;
    color: white;
}

.error-404 .btn-primary:hover {
    background: #2980b9;
    transform: translateY(-2px);
}

.error-404 .btn-secondary {
    background: #f8f9fa;
    color: #1a5276;
    border: 2px solid #1a5276;
}

.error-404 .btn-secondary:hover {
    background: #1a5276;
    color: white;
    transform: translateY(-2px);
}

.error-404 .suggestions {
    margin-top: 3rem;
    background: #f8f9fa;
    padding: 2rem;
    border-radius: 12px;
    max-width: 600px;
}

.error-404 .suggestions h3 {
    color: #1a5276;
    margin-bottom: 1rem;
}

.error-404 .suggestions ul {
    list-style: none;
    padding: 0;
}

.error-404 .suggestions li {
    padding: 0.5rem 0;
    border-bottom: 1px solid #e9ecef;
}

.error-404 .suggestions li:last-child {
    border-bottom: none;
}

.error-404 .suggestions a {
    color: #2980b9;
    text-decoration: none;
}

.error-404 .suggestions a:hover {
    text-decoration: underline;
}

@media (max-width: 768px) {
    .error-404 .code {
        font-size: 5rem;
    }
    
    .error-404 .message {
        font-size: 1.2rem;
    }
    
    .error-404 .actions {
        flex-direction: column;
        align-items: center;
    }
    
    .error-404 .btn {
        width: 200px;
        justify-content: center;
    }
}
</style>
</head>
<body>
<?php require 'includes/navigation.php'; ?>

<div class="error-404">
    <div class="code">404</div>
    <div class="message">Halaman Tidak Ditemukan</div>
    <div class="description">
        Maaf, halaman yang Anda cari tidak tersedia atau telah dipindahkan. 
        Silakan periksa URL atau gunakan navigasi di bawah ini.
    </div>
    
    <div class="actions">
        <a href="/" class="btn btn-primary">🏠 Beranda</a>
        <a href="javascript:history.back()" class="btn btn-secondary">⬅️ Kembali</a>
    </div>
    
    <div class="suggestions">
        <h3>📚 Halaman yang Mungkin Anda Cari:</h3>
        <ul>
            <li><a href="/pages/login.php">🔐 Login / Masuk</a></li>
            <li><a href="/pages/register.php">📝 Registrasi / Daftar</a></li>
            <li><a href="/pages/tryout.php">📋 Mulai Tryout</a></li>
            <li><a href="/pages/materi.php">📚 Materi Pembelajaran</a></li>
            <li><a href="/pages/leaderboard.php">🏆 Papan Peringkat</a></li>
            <li><a href="/pages/feedback.php">💬 Kirim Feedback</a></li>
        </ul>
    </div>
</div>

<?php require 'includes/footer.php'; ?>

<script>
// Log 404 errors for debugging
fetch('/api/log_error.php', {
    method: 'POST',
    headers: {'Content-Type': 'application/json'},
    body: JSON.stringify({
        type: '404',
        url: window.location.href,
        referrer: document.referrer,
        user_agent: navigator.userAgent,
        timestamp: new Date().toISOString()
    })
}).catch(() => {
    // Silently fail if logging fails
});
</script>

</body>
</html>
