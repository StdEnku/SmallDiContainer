# SmallDiContainer
最小構成のリフレクションを利用したDIコンテナ

DIコンテナの本質はクラスとファクトリーメソッドのリストであり、本プロジェクトでは<br>
[クラス名 => ファクトリーメソッド]という連想配列をクラス登録毎に追加していくというものである。<br>
もしファクトリーメソッドがなければリフレクションを使用してコンストラクタ引数を解析して自動的に生成する。

# SmallDiContainer 利用ガイド & サンプルコード

`SmallDiContainer` は、**「1ファイルで完結し、パッケージマネージャーが使えない環境でもファイルのコピペだけで手軽に導入できる」**ことを目的に設計された、軽量なPHP用DI（依存性注入）コンテナです。

* **完全な自己完結 (Single-File)**: 外部ライブラリへの依存が一切なく、コンテナ本体のコードをプロジェクトにコピーするだけで動作します。
* **オフライン・制限された環境に最適**: Composerなどのパッケージマネージャー（Vendor管理）が使えない現場や、レガシーな環境、小規模なスクリプト構成でも、**ソースコードのコピペだけ**ですぐにモダンな依存性注入の恩恵を受けられます。

---

## 💻 動作検証サンプルコード

以下のコードは、クラス `B` がクラス `A` に依存している構造に対し、ファクトリー登録またはオートワイアリング（自動解決）を切り替えてインスタンスを解決するサンプルです。

```php
<?php

require_once __DIR__ . '/SmallDiContainer.php';

class A {
    public function func() : void {
        echo "A\n";
    }
}

class B {
    private A $_a;

    public function __construct(A $a) {
        $this->_a =$a;
    }

    public function func() : void {
        echo "B\n";
    }
}

// 1. カスタムファクトリーの定義
$aFactory = function(\SmallDiContainer\DiContainer $container) : A {
    return new A();
};

$bFactory = function(\SmallDiContainer\DiContainer $container) : B {
    $a =$container->resolve(A::class);
    return new B($a);
};

$container = \SmallDiContainer\DiContainer::getInstance();

// 2. 登録方法の切り替え（true: ファクトリー使用 / false: オートワイアリング）
if (true) {
    $container->register(A::class,$aFactory);
    $container->register(B::class,$bFactory);
} else {
    $container->register(A::class);$container->register(B::class);
}

// 3. インスタンスの解決と実行
$a =$container->resolve(A::class);
$b =$container->resolve(B::class);

$a->func(); // A
$b->func(); // B

echo "Hello, World!";
```
