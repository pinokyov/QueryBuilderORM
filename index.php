<?php
require_once __DIR__ . '/bootstrap.php';

use App\Models\User;
use App\Models\Post;
use App\Orm\Database;

// Veritabanı yapılandırması
$config = [
    'host' => DB_HOST,
    'port' => DB_PORT,
    'database' => DB_DATABASE,
    'username' => DB_USERNAME,
    'password' => DB_PASSWORD,
    'charset' => 'utf8mb4',
    'options' => [
        \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
        \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
        \PDO::ATTR_EMULATE_PREPARES => false,
    ]
];

Database::setConfig($config);

function runExample() {
    ob_start();
    
    try {
        echo "<div class='alert alert-info mb-4'><h4 class='alert-heading'>Mini PHP ORM Örnek Uygulama</h4></div>";
        
        // 1. Yeni Kullanıcı Oluşturma
        echo "<div class='card mb-4'>";
        echo "<div class='card-header'><h4>1. Yeni Kullanıcı Oluşturma</h4></div>";
        echo "<div class='card-body'>";
        $user = new User([
            'name' => 'Ahmet Yılmaz',
            'email' => 'ahmet@example.com',
            'password' => password_hash('sifre123', PASSWORD_DEFAULT),
        ]);
        
        $user->save();
        echo "<div class='alert alert-success mb-0'><strong>Yeni kullanıcı oluşturuldu:</strong> " . 
             htmlspecialchars($user->name) . " (ID: " . $user->id . ")</div>";
        echo "</div></div>";
        
        // 2. Kullanıcı için Gönderi Oluşturma
        echo "<div class='card mb-4'>";
        echo "<div class='card-header'><h4>2. Kullanıcı İçin Gönderi Oluşturma</h4></div>";
        echo "<div class='card-body'>";
        
        $post1 = new Post([
            'title' => 'İlk Gönderi',
            'content' => 'Bu benim ilk gönderim. Merhaba dünya!',
            'user_id' => $user->id,
        ]);
        $post1->save();
        
        $post2 = new Post([
            'title' => 'İkinci Gönderi',
            'content' => 'Bu da ikinci gönderim. ORM harika çalışıyor!',
            'user_id' => $user->id,
        ]);
        $post2->save();
        
        echo "<p class='mb-0'><strong>Gönderiler oluşturuldu:</strong></p>";
        echo "<ul class='mb-0'>";
        echo "<li>" . htmlspecialchars($post1->title) . " (ID: " . $post1->id . ")</li>";
        echo "<li>" . htmlspecialchars($post2->title) . " (ID: " . $post2->id . ")</li>";
        echo "</ul>";
        echo "</div></div>";
        
        // 3. Kullanıcı Bilgilerini Görüntüleme
        echo "<div class='card mb-4'>";
        echo "<div class='card-header'><h4>3. Kullanıcı Bilgileri</h4></div>";
        echo "<div class='card-body'>";
        
        $foundUser = User::find($user->id);
        if ($foundUser) {
            echo "<div class='mb-3'>";
            echo "<h5>" . htmlspecialchars($foundUser->name ?? '') . "</h5>";
            echo "<p class='mb-1'><strong>E-posta:</strong> " . htmlspecialchars($foundUser->email ?? '') . "</p>";
            
            // Kullanıcının gönderilerini getir
            $userPosts = Post::query()
                ->where('user_id', '=', $foundUser->id)
                ->get();
                
            $postCount = count($userPosts);
            echo "<p class='mb-1'><strong>Toplam Gönderi:</strong> " . $postCount . "</p>";
            
            if ($postCount > 0) {
                echo "<p class='mb-1'><strong>Gönderiler:</strong></p>";
                echo "<ul class='list-group'>";
                foreach ($userPosts as $post) {
                    echo "<li class='list-group-item'>";
                    echo "<strong>" . htmlspecialchars($post->title) . "</strong><br>";
                    echo "<small>" . htmlspecialchars($post->content) . "</small>";
                    echo "</li>";
                }
                echo "</ul>";
            }
            echo "</div>";
        }
        echo "</div></div>";
        
        // 4. Kullanıcı Bilgilerini Güncelleme
        echo "<div class='card mb-4'>";
        echo "<div class='card-header'><h4>4. Kullanıcı Bilgilerini Güncelleme</h4></div>";
        echo "<div class='card-body'>";
        
        $user->name = 'Ahmet Yılmaz (Güncellendi)';
        $user->save();
        echo "<p class='mb-0'><strong>Kullanıcı adı güncellendi:</strong> " . htmlspecialchars($user->name) . "</p>";
        echo "</div></div>";
        
        // 5. Tüm Kullanıcıları Listeleme
        echo "<div class='card mb-4'>";
        echo "<div class='card-header'><h4>5. Tüm Kullanıcılar</h4></div>";
        echo "<div class='card-body'>";
        
        // Tüm kullanıcıları getir
        $allUsers = User::all();
        
        // Her kullanıcı için post sayılarını al
        $usersWithPostCounts = [];
        foreach ($allUsers as $user) {
            $userArray = $user->toArray();
            $userArray['post_count'] = Post::query()
                ->where('user_id', '=', $user->id)
                ->count();
            $usersWithPostCounts[] = (object)$userArray;
        }
        
        if (count($usersWithPostCounts) > 0) {
            echo "<div class='table-responsive'>";
            echo "<table class='table table-striped'>";
            echo "<tbody>";
            
            foreach ($usersWithPostCounts as $index => $user) {
                echo "<tr>";
                echo "<td>" . ($index + 1) . "</td>";
                echo "<td>" . htmlspecialchars($user->name ?? '') . "</td>";
                echo "<td>" . htmlspecialchars($user->email ?? '') . "</td>";
                echo "<td>" . ($user->post_count ?? 0) . "</td>";
                echo "</tr>";
            }
            
            echo "</tbody></table></div>";
        } else {
            echo "<p class='mb-0'>Henüz kullanıcı bulunmamaktadır.</p>";
        }
        
        echo "</div></div>";
        
        // 6. Örnek Sorgular
        echo "<div class='card mb-4'>";
        echo "<div class='card-header'><h4>6. Örnek Sorgular</h4></div>";
        echo "<div class='card-body'>";
        
        // Örnek 1: Belirli bir e-posta adresine sahip kullanıcıyı bulma
        echo "<h5>Örnek 1: Belirli bir e-posta adresine sahip kullanıcı</h5>";
        $foundByEmail = User::query()
            ->where('email', '=', 'ahmet@example.com')
            ->first();
            
        if ($foundByEmail) {
            // Kullanıcının post sayısını ayrıca al
            $postCount = Post::query()
                ->where('user_id', '=', $foundByEmail->id)
                ->count();
            
            echo "<div class='alert alert-success'>";
            echo "<p class='mb-0'>Kullanıcı bulundu: <strong>" . 
                 htmlspecialchars($foundByEmail->name) . "</strong> (" . 
                 htmlspecialchars($foundByEmail->email) . ") - " . 
                 $postCount . " gönderi";
            echo "</p></div>";
        } else {
            echo "<div class='alert alert-warning'><p class='mb-0'>Kullanıcı bulunamadı.</p></div>";
        }
        
        // Örnek 2: Tüm gönderileri yazarlarıyla birlikte getir
        echo "<h5 class='mt-4'>Örnek 2: Tüm gönderiler</h5>";
        $allPosts = Post::query()
            ->join('users', 'posts.user_id', '=', 'users.id')
            ->select('posts.*', 'users.name as author_name')
            ->orderBy('posts.created_at', 'desc')
            ->get();
        
        if (!empty($allPosts)) {
            echo "<div class='list-group'>";
            foreach ($allPosts as $post) {
                echo "<div class='list-group-item'>";
                echo "<div class='d-flex justify-content-between align-items-start'>";
                echo "<div class='me-3'>";
                echo "<h6 class='mb-1'>" . htmlspecialchars($post->title ?? 'Başlıksız') . "</h6>";
                if (!empty($post->author_name)) {
                    echo "<small class='text-muted'>Yazar: " . htmlspecialchars($post->author_name) . "</small>";
                }
                echo "</div>";
                echo "</div>";
                
                if (!empty($post->content)) {
                    $content = strlen($post->content) > 150 ? substr($post->content, 0, 150) . '...' : $post->content;
                    echo "<div class='mt-2'>" . nl2br(htmlspecialchars($content)) . "</div>";
                }
                
                echo "</div>";
            }
            echo "</div>";
        } else {
            echo "<div class='alert alert-info'><p class='mb-0'>Henüz gönderi bulunmamaktadır.</p></div>";
        }
        
        echo "</div></div>";
        
    } catch (Exception $e) {
        echo "<div class='alert alert-danger'><strong>Hata oluştu:</strong> " . 
             htmlspecialchars($e->getMessage()) . "</div>";
    }
    
    return ob_get_clean();
}

// Form gönderildiyse örneği çalıştır
$output = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $output = runExample();
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PHP ORM Örneği</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body { padding: 20px; }
        .container { max-width: 1200px; margin: 0 auto; }\n        h1 { margin-bottom: 30px; }
        .section { margin-bottom: 40px; }
    </style>
</head>
<body>
    <div class="container">
        <h1 class="text-center mb-5">PHP ORM Örnek Uygulaması</h1>
        
        <?php if (empty($output)): ?>
            <div class="card">
                <div class="card-body text-center">
                    <h5 class="card-title">ORM Örneğini Çalıştır</h5>
                    <p class="card-text">Aşağıdaki butona tıklayarak ORM örneğini çalıştırabilirsiniz.</p>
                    <form method="post">
                        <button type="submit" class="btn btn-primary">Örneği Çalıştır</button>
                    </form>
                </div>
            </div>
        <?php else: ?>
            <div class="text-end mb-3">
                <a href="/" class="btn btn-outline-primary">Yeniden Başlat</a>
            </div>
            <div class="section">
                <?= $output ?>
            </div>
        <?php endif; ?>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
