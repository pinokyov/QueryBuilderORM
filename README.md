# Mini PHP ORM

Minimal, genişletilebilir bir PHP ORM (Nesne-İlişkisel Eşleştirici) kütüphanesi. Veritabanı işlemlerinizi kolay ve anlaşılır bir şekilde yönetmenizi sağlar.

## Özellikler

- Basit ve anlaşılır API
- Akıcı sorgu oluşturucu
- Model ilişkileri (hasOne, hasMany, belongsTo)
- Veri türü dönüşümleri ve özellik yönetimi
- JSON serileştirme
- Otomatik zaman damgaları
- Hafif ve kolay genişletilebilir yapı

## Gereksinimler

- PHP 8.0 veya üzeri
- PDO eklentisi
- MySQL 5.7+ veya MariaDB 10.3+
- Docker ve Docker Compose (önerilen)

## Hızlı Başlangıç (Docker ile)

1. **Projeyi Klonlayın**
   ```bash
   git clone [repo-adresi]
   cd QueryBuilderORM
   ```

2. **Docker Konteynerlerini Başlatın**
   ```bash
   docker-compose up -d --build
   ```
   Bu komut aşağıdaki servisleri başlatacaktır:
   - PHP 8.1 + Apache
   - MySQL 8.0
   - Adminer (Veritabanı yönetim arayüzü)

3. **Veritabanını İçe Aktarın (Adminer ile)**
   1. Tarayıcınızda şu adrese gidin: [http://localhost:8080](http://localhost:8080)
   2. Giriş bilgileri:
      - Sistem: **MySQL**
      - Sunucu: **orm_mysql**
      - Kullanıcı adı: **orm_user**
      - Şifre: **orm_password**
      - Veritabanı: **query_builder_orm**
   3. "SQL komutu" sekmesine tıklayın
   4. `database.sql` dosyasının içeriğini yapıştırın ve "Yürüt" butonuna tıklayın

4. **Uygulamayı Görüntüleyin**
   - Ana uygulama: [http://localhost:8081](http://localhost:8081)
   - Veritabanı yönetimi: [http://localhost:8080](http://localhost:8080)

## Manuel Kurulum (Docker Olmadan)

1. **Gereksinimleri Yükleyin**
   - PHP 8.0+ ve gerekli eklentiler
   - MySQL 5.7+ veya MariaDB 10.3+
   - Composer

2. **Bağımlılıkları Yükleyin**
   ```bash
   composer install
   ```

3. **Veritabanını Yapılandırın**
   - `config/database.php` dosyasını düzenleyerek veritabanı bağlantı bilgilerinizi güncelleyin
   - `database.sql` dosyasını kullanarak veritabanı şemasını oluşturun

4. **Uygulamayı Çalıştırın**
   ```bash
   php -S localhost:8000 -t public
   ```
   - Uygulama şu adreste erişilebilir olacaktır: [http://localhost:8000](http://localhost:8000)

## Kullanım Örnekleri

Temel kullanım örnekleri için `example.php` dosyasını inceleyebilir veya web arayüzü üzerinden denemeler yapabilirsiniz.

```php
// Örnek Kullanım
$user = new User([
    'name' => 'Ahmet Yılmaz',
    'email' => 'ahmet@example.com'
]);
$user->save();
```

## Docker Komutları

- **Tüm konteynerleri başlat:** `docker-compose up -d`
- **Konteynerleri durdur:** `docker-compose down`
- **Logları görüntüle:** `docker-compose logs -f`
- **Veritabanına bağlan:** `docker-compose exec db mysql -u orm_user -porm_password query_builder_orm`

## Katkıda Bulunma

1. Fork'layın
2. Yeni bir branch oluşturun (`git checkout -b yeni-ozellik`)
3. Değişikliklerinizi commit edin (`git commit -am 'Yeni özellik eklendi'`)
4. Branch'e push edin (`git push origin yeni-ozellik`)
5. Pull Request oluşturun

## Model Tanımlama

```php
use App\Orm\Model;

class User extends Model
{
    protected string $table = 'users';
    protected string $primaryKey = 'id';
    protected array $fillable = ['name', 'email', 'password'];
    protected array $hidden = ['password'];
    protected array $casts = [
        'email_verified_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
```

## Temel İşlemler

### Kayıt İşlemleri (CRUD)

```php
// Yeni kayıt
$user = new User();
$user->name = 'Ahmet Yılmaz';
$user->email = 'ahmet@example.com';
$user->password = password_hash('sifre123', PASSWORD_DEFAULT);
$user->save();

// Tek kayıt getirme
$user = User::find(1);

// Tüm kayıtları getirme
$users = User::all();

// Güncelleme
$user->name = 'Ahmet Yılmaz (Güncellendi)';
$user->save();

// Silme
$user->delete();
```

### Sorgu Oluşturucu

```php
// Temel where kullanımı
$users = User::query()
    ->where('aktif', '=', 1)
    ->orderBy('ad', 'ASC')
    ->limit(10)
    ->get();

// Koşullu sorgular
$yoneticiler = User::query()
    ->where('rol', '=', 'yonetici')
    ->orWhere('admin_mi', '=', 1)
    ->get();

// Sayfalama ve sıralama
$sayfa = $_GET['sayfa'] ?? 1;
$kayitSayisi = 15;

$kullanicilar = User::query()
    ->orderBy('olusturulma_tarihi', 'DESC')
    ->paginate($kayitSayisi, $sayfa);
```

### İlişkiler

```php
// User modeli (users tablosu)
class User extends Model
{
    public function posts()
    {
        return $this->hasMany(Post::class, 'kullanici_id');
    }
}

// Post modeli (posts tablosu)
class Post extends Model
{
    public function user()
    {
        return $this->belongsTo(User::class, 'kullanici_id');
    }
    
    public function comments()
    {
        return $this->hasMany(Comment::class, 'yazi_id');
    }
}

// Kullanım örnekleri
$user = User::find(1);
$posts = $user->posts; // Kullanıcının tüm yazıları

$post = Post::first();
$author = $post->user; // Yazının yazarı
$comments = $post->comments; // Yazıya ait yorumlar
```

## Test Etme

1. Test veritabanı ayarlarını `phpunit.xml` dosyasında yapılandırın
2. Testleri çalıştırın:
   ```bash
   ./vendor/bin/phpunit
   ```

## Lisans

Bu proje MIT lisansı altında lisanslanmıştır.
