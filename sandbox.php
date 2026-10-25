<?php

require_once __DIR__ . '/SmallDiContainer.php';

class A{
    public function __construct() {
        echo "Aをコンストラクタで実体化\n";
    }

    public function func() : void {
        echo "Aのメソッド実行\n";
    }
}

class B{
    private A $_a;

    public function __construct(A $a) {
        echo "Bをコンストラクタで実体化\n";
        $this->_a = $a;
    }

    public function func() : void {
        echo "Bのメソッド実行\n";
    }
}

$aFactory = function(\SmallDiContainer\DiContainer $container) : A {
    echo "Aをファクトリーメソッドで実体化\n";
    return new A();
};

$bFactory = function(\SmallDiContainer\DiContainer $container) : B {
    echo "Bをファクトリーメソッドで実体化\n";
    $a = $container->resolve(A::class);
    return new B($a);
};

$container = \SmallDiContainer\DiContainer::getInstance();

// ファクトリーメソッドを登録する場合ture
$container->register(A::class, true);
$container->register(B::class, false, $bFactory);

$a1 = $container->resolve(A::class);
$b1 = $container->resolve(B::class);
$a2 = $container->resolve(A::class);
$b2 = $container->resolve(B::class);

$a1->func(); // A
$b1->func(); // B

if ($a1 === $a2) {
    echo "a1とa2は同一オブジェクト\n";
}else {
    echo "a1とa2は別オブジェクト\n";
}

if ($b1 === $b2) {
    echo "b1とb2は同一オブジェクト\n";
}else {
    echo "b1とb2は別オブジェクト\n";
}