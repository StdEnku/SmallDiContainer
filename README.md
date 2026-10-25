# SmallDiContainer
最小構成のリフレクションを利用したDIコンテナ

DIコンテナの本質はクラスとファクトリーメソッドのリストであり、本プロジェクトでは<br>
[クラス名 => [ファクトリー関数, シングルトンフラグ]] という連想配列を管理するというものである。<br>
もしファクトリー関数が指定されていなければ、リフレクションを使用してコンストラクタ引数を解析して自動的に生成（オートワイアリング）する。

# SmallDiContainer 利用ガイド & サンプルコード

`SmallDiContainer` は、**「1ファイルで完結し、パッケージマネージャーが使えない環境でもファイルのコピペだけで手軽に導入できる」**ことを目的に設計された、軽量なPHP用DI（依存性注入）コンテナです。

- **完全な自己完結 (Single-File)**: 外部ライブラリへの依存が一切なく、コンテナ本体のコードをプロジェクトにコピーするだけで動作します。
- **堅牢なファクトリーバリデーション**: クロージャを登録する際、引数が `DiContainer` であることや、戻り値が登録するクラスと一致するかを `ReflectionFunction` で厳密に検証します。
- **ライフサイクル管理**: シングルトン（共有インスタンス）とトランジェント（都度生成）の切り替えをサポートしています。

---

## 💻 動作検証サンプルコード

以下のコードは、クラス `B` がクラス `A` に依存している構造に対し、シングルトンとトランジェント、さらにカスタムファクトリーと自動解決を組み合わせてインスタンスを解決するサンプルです。

```php
<?php

require_once __DIR__ . '/SmallDiContainer.php';

class A {
    public function __construct() {
        echo "Aをコンストラクタで実体化\n";
    }

    public function func() : void {
        echo "Aのメソッド実行\n";
    }
}

class B {
    private A $_a;

    public function __construct(A $a) {
        echo "Bをコンストラクタで実体化\n";
        $this->_a =$a;
    }

    public function func() : void {
        echo "Bのメソッド実行\n";
    }
}

// 1. カスタムファクトリーの定義（引数にDiContainer、戻り値に該当クラスを指定する必要がある）
$bFactory = function(\SmallDiContainer\DiContainer$container) : B {
    echo "Bをファクトリーメソッドで実体化\n";
    $a =$container->resolve(A::class);
    return new B($a);
};

$container = \SmallDiContainer\DiContainer::getInstance();

// 2. クラスの登録
// 第1引数: クラス名
// 第2引数: シングルトンフラグ (true: シングルトン, false: トランジェント)
// 第3引数: カスタムファクトリークロージャ (省略時は自動解決)
$container->register(A::class, true); // Aはシングルトン（自動解決）
$container->register(B::class, false,$bFactory); // Bはトランジェント（カスタムファクトリー使用）

// 3. インスタンスの解決と実行
$a1 =$container->resolve(A::class);
$b1 =$container->resolve(B::class);
$a2 =$container->resolve(A::class);
$b2 =$container->resolve(B::class);

$a1->func();$b1->func(); 

// 4. 同一性の確認
if ($a1 ===$a2) {
    echo "a1とa2は同一オブジェクト（シングルトン）\n";
} else {
    echo "a1とa2は別オブジェクト\n";
}

if ($b1 ===$b2) {
    echo "b1とb2は同一オブジェクト\n";
} else {
    echo "b1とb2は別オブジェクト（トランジェント）\n";
}
'''
