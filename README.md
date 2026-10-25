# SmallDiContainer
最小構成のリフレクションを利用したDIコンテナ

DIコンテナの本質はクラスとファクトリーメソッドのリストであり、本プロジェクトでは<br>
[クラス名 => [ファクトリー関数, シングルトンフラグ]] という連想配列を管理するというものである。<br>
もしファクトリー関数が指定されていなければ、リフレクションを使用してコンストラクタ引数を解析して自動的に生成（オートワイアリング）する。

# SmallDiContainer Documentation

`SmallDiContainer\DiContainer` は、PHP 向けの軽量な DI（Dependency Injection: 依存性注入）コンテナです。自動的な依存関係の解決（オートワイヤリング）や、ファクトリー関数を用いた柔軟なインスタンス生成、シングルトンライフサイクルの管理に対応しています。

---

## 主な機能

1. **シングルトンパターンのコンテナ**
   `DiContainer::getInstance()` を使用してアプリ全体で単一のコンテナインスタンスを共有できます。
2. **自動依存関係解決 (Auto-Wiring)**
   コンストラクタの型ヒントをもとに、依存しているクラスを自動的に再帰解決します。
3. **ファクトリー関数の登録**
   複雑な初期化処理が必要なクラスには、クロージャを用いたカスタムファクトリー関数を指定可能です（厳格な型チェック機能付き）。
4. **ライフサイクル管理**
   `Transient`（解決ごとに新規生成）と `Singleton`（初回生成時のインスタンスを再利用）を切り替えられます。

---

## 基本的な使い方

### 1. コンテナの取得

コンテナのインスタンスは `getInstance()` メソッドを通じて取得します。

```php
use SmallDiContainer\DiContainer;

$container = DiContainer::getInstance();
```

---

### 2. クラスの登録 (`register`)

`register` メソッドを使用して、コンテナにクラスを登録します。

```php
public function register(string $className, bool $isSingleton = false, ?\Closure $factory = null): void
```

#### 引数説明
- **`$className`** (string): 登録するクラスの完全修飾名 (`Class::class`)。
- **`$isSingleton`** (bool): `true` の場合はシングルトンとして扱われ、`false` の場合は参照ごとに新しいインスタンスが生成されます（デフォルト: `false`）。
- **`$factory`** (?\Closure): 独自のインスタンス化ロジックが必要な場合に指定します（デフォルト: `null`）。

---

### 3. クラスの解決・取得 (`resolve`)

`resolve` メソッドを使用して、登録されたクラスのインスタンスを取得します。

```php
$service = $container->resolve(UserService::class);
```

---

## 具体的な使用例

### 例 1: 基本的な依存関係の自動解決 (Auto-Wiring)

コンストラクタで他のクラスに依存している場合、コンテナが自動的に依存クラスを解決して注入します。

```php
namespace App;

use SmallDiContainer\DiContainer;

class Logger {
    public function log(string $message) {
        echo "[LOG]: {$message}\n";
    }
}

class UserRepository {
    public function __construct(private Logger $logger) {}

    public function find(int $id) {
        $this->logger->log("User {$id} を取得しました");
        return ['id' => $id, 'name' => 'Alice'];
    }
}

$container = DiContainer::getInstance();

// 依存される側のクラスと、依存する側のクラスの両方を登録
$container->register(Logger::class);
$container->register(UserRepository::class);

// UserRepository を取得すると、内部で自動的に Logger が注入されます
$userRepository = $container->resolve(UserRepository::class);
$userRepository->find(1);
```

---

### 例 2: シングルトンモードでの登録

`$isSingleton` に `true` を指定することで、常に同じインスタンスを返すように設定できます。

```php
$container->register(DatabaseConnection::class, true);

$db1 = $container->resolve(DatabaseConnection::class);
$db2 = $container->resolve(DatabaseConnection::class);

// $db1 と $db2 は完全に行一のインスタンスになります
var_dump($db1 === $db2); // bool(true)
```

---

### 例 3: カスタムファクトリー関数の利用

複雑な初期化（環境変数の読み込みや特定オプションの付与など）が必要な場合、ファクトリー関数（クロージャ）を渡すことができます。

#### ⚠️ ファクトリー関数の制約（バリデーション規則）
ファクトリー関数を指定する場合、**厳格なシグネチャ**に従う必要があります：
1. **第1引数**: `DiContainer` 型の引数を1つ受け取ること
2. **戻り値の型**: 登録する `$className` と一致する型ヒントを指定すること

```php
class ApiClient {
    public function __construct(public string $apiKey, public Logger $logger) {}
}

$container->register(Logger::class);

// ファクトリー関数の登録例
$container->register(
    ApiClient::class,
    false, // シングルトンフラグ
    function (DiContainer $c): ApiClient {
        // コンテナ経由で他の依存関係を取得可能
        $logger = $c->resolve(Logger::class);
        $apiKey = "SECRET_API_KEY_12345";
        
        return new ApiClient($apiKey, $logger);
    }
);

$apiClient = $container->resolve(ApiClient::class);
```

---

## 例外処理 (Exceptions)

以下のケースでは `\Exception` がスローされます。

1. **未登録のクラスを参照した場合**
   `resolve()` にコンテナへ登録されていないクラス名を渡した際。
2. **ファクトリー関数のシグネチャ違反**
   `register()` の際、ファクトリー関数の「引数の型・数」または「戻り値の型ヒント」が規則を満たしていない場合。
