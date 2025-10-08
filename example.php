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

echo "=== Mini PHP ORM Örnek Uygulama ===\n\n";

try {
    // 1. Yeni Kullanıcı Oluşturma
    echo "=== 1. Yeni Kullanıcı Oluşturma ===\n";
    $user = new User([
        'name' => 'Ahmet Yılmaz',
        'email' => 'ahmet@example.com',
        'password' => password_hash('sifre123', PASSWORD_DEFAULT),
    ]);
    
    $user->save();
    echo "- Yeni kullanıcı oluşturuldu: " . $user->name . " (ID: " . $user->id . ")\n";
    
    // Debug: Show raw user data
    echo "\n[DEBUG] Kullanıcı verileri:\n";
    echo "- created_at: " . ($user->created_at ? (is_object($user->created_at) ? get_class($user->created_at) : gettype($user->created_at)) : 'NULL') . "\n";
    echo "- updated_at: " . ($user->updated_at ? (is_object($user->updated_at) ? get_class($user->updated_at) : gettype($user->updated_at)) : 'NULL') . "\n";
    
    // Try to format the dates if they exist
    if ($user->created_at) {
        if (is_object($user->created_at) && method_exists($user->created_at, 'format')) {
            echo "- created_at (formatted): " . $user->created_at->format('Y-m-d H:i:s') . "\n";
        } else {
            echo "- created_at (raw): " . $user->created_at . "\n";
        }
    }
    
    if ($user->updated_at) {
        if (is_object($user->updated_at) && method_exists($user->updated_at, 'format')) {
            echo "- updated_at (formatted): " . $user->updated_at->format('Y-m-d H:i:s') . "\n";
        } else {
            echo "- updated_at (raw): " . $user->updated_at . "\n";
        }
    }
    
    // Debug: Show raw database data
    $db = \App\Orm\Database::getConnection();
    $stmt = $db->query("SELECT created_at, updated_at FROM users WHERE id = " . $user->id);
    $dbData = $stmt->fetch(\PDO::FETCH_ASSOC);
    echo "\n[DEBUG] Veritabanından ham tarih bilgileri:\n";
    echo "- created_at: " . (isset($dbData['created_at']) ? var_export($dbData['created_at'], true) : 'NULL') . "\n";
    echo "- updated_at: " . (isset($dbData['updated_at']) ? var_export($dbData['updated_at'], true) : 'NULL') . "\n\n";
    
    // Debug: Show model attributes
    echo "[DEBUG] Model özellikleri:\n";
    echo "- attributes: " . print_r($user->getAttributes(), true) . "\n";
    
    // Debug: Check casts
    if (method_exists($user, 'getCasts')) {
        echo "- casts: " . print_r($user->getCasts(), true) . "\n";
    }
    
    // 2. Kullanıcı için Gönderi Oluşturma
    echo "=== 2. Kullanıcı İçin Gönderi Oluşturma ===\n";
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
    
    echo "- İlk gönderi oluşturuldu: " . $post1->title . " (ID: " . $post1->id . ")\n";
    echo "- İkinci gönderi oluşturuldu: " . $post2->title . " (ID: " . $post2->id . ")\n\n";
    
    // 3. Kullanıcı Bilgilerini Görüntüleme
    echo "=== 3. Kullanıcı Bilgileri ===\n";
    $foundUser = User::find($user->id);
    if ($foundUser) {
        echo "- Kullanıcı Adı: " . $foundUser->name . "\n";
        echo "- E-posta: " . $foundUser->email . "\n";
        
        // Get user's posts using a direct query for better performance
        $userPosts = Post::query()
            ->where('user_id', '=', $foundUser->id)
            ->get();
            
        $postCount = is_array($userPosts) ? count($userPosts) : 0;
        
        echo "- " . $foundUser->name . " kullanıcısının gönderileri (Toplam: " . $postCount . "):\n";
        
        if ($postCount > 0) {
            foreach ($userPosts as $post) {
                $postTitle = is_object($post) ? $post->title : $post['title'];
                $postId = is_object($post) ? $post->id : $post['id'];
                $postContent = is_object($post) ? $post->content : $post['content'];
                
                echo "  - " . $postTitle . " (ID: " . $postId . ")\n";
                echo "    İçerik: " . substr($postContent, 0, 50) . "...\n";
            
            }
        } else {
            echo "  - Henüz gönderi bulunmamaktadır.\n";
        }
        echo "\n";
    }
    
    // 4. Gönderi Detaylarını Görüntüleme
    echo "=== 4. Gönderi Detayları ===\n";
    $firstPost = Post::find($post1->id);
    if ($firstPost) {
        echo "- Gönderi Başlığı: " . $firstPost->title . "\n";
        echo "- İçerik: " . $firstPost->content . "\n";
        
        // Gönderinin yazarını getir
        $author = $firstPost->user();
        if ($author) {
            $author = $author->getResults();
            echo "- Yazar: " . $author->name . " (ID: " . $author->id . ")\n";
        }
    }
    
    // 5. Kullanıcı Bilgilerini Güncelleme
    echo "=== 5. Kullanıcı Bilgilerini Güncelleme ===\n";
    $user->name = 'Ahmet Yılmaz (Güncellendi)';
    $user->save();
    echo "- Kullanıcı adı güncellendi: " . $user->name . "\n\n";
    
    // 6. Tüm Kullanıcıları Listeleme
    echo "=== 6. Tüm Kullanıcılar ===\n";
    $allUsers = User::all();
    foreach ($allUsers as $index => $user) {
        // Ensure we have a User model instance
        $userModel = ($user instanceof User) ? $user : User::find($user->id);
        echo ($index + 1) . ". " . $userModel->name . " (" . $userModel->email . ") \n";
    }
    echo "\n";
    
    // 7. Koşullu Sorgu Örneği
    echo "=== 7. Koşullu Sorgu Örneği ===\n";
    echo "- 'example.com' uzantılı e-postaya sahip kullanıcılar ve gönderileri:\n";
    
    // Get all users with example.com email
    $exampleUsers = User::query()
        ->where('email', 'LIKE', '%@example.com')
        ->with('posts')
        ->get();
    
    // Display users with their post counts
    foreach ($exampleUsers as $user) {
        // Get the posts relationship
        $posts = $user->posts;
        
        // If posts is a relationship object, get the results
        if (is_object($posts) && method_exists($posts, 'getResults')) {
            $posts = $posts->getResults();
        }
        
        // Ensure posts is an array
        $posts = is_array($posts) ? $posts : [];
        $postCount = count($posts);
        $userName = is_object($user) ? ($user->name ?? '') : ($user['name'] ?? '');
        $userEmail = is_object($user) ? ($user->email ?? '') : ($user['email'] ?? '');
        
        echo "  - " . $userName . " (" . $userEmail . "): " . $postCount . " gönderi\n";
        
        // Display post titles if available
        if ($postCount > 0) {
            foreach ($posts as $post) {
                $title = is_object($post) ? ($post->title ?? '') : ($post['title'] ?? '');
                echo "    * " . $title . "\n";
            }
        }
    }
    
    echo "\n=== İşlem Tamamlandı ===\n";
    
} catch (Exception $e) {
    echo "\n!!! HATA OLUŞTU !!!\n";
    echo "Hata Mesajı: " . $e->getMessage() . "\n";
    echo "Dosya: " . $e->getFile() . " (Satır: " . $e->getLine() . ")\n\n";
    echo "Stack Trace:\n" . $e->getTraceAsString() . "\n";
}
