<?php

require_once __DIR__ . '/SmallDiContainer.php';

class A{
    public function __construct() {
        echo "Aをコンストラクタで実体化\n";
    }

    public function Func() : void {
        echo "Aのメソッド実行\n";
    }
}

class B{
    private A $_a;

    public function __construct(A $a) {
        echo "Bをコンストラクタで実体化\n";
        $this->_a = $a;
    }

    public function Func() : void {
        echo "Bのメソッド実行\n";
    }
}

$aFactory = function(\SmallDiContainer\DiContainer $container) : A {
    echo "Aをファクトリーメソッドで実体化\n";
    return new A();
};

$bFactory = function(\SmallDiContainer\DiContainer $container) : B {
    echo "Bをファクトリーメソッドで実体化\n";
    $a = $container->Resolve(A::class);
    return new B($a);
};

$container = \SmallDiContainer\DiContainer::GetInstance();

// ファクトリーメソッドを登録する場合ture
$container->Register(A::class, true);
$container->Register(B::class, false, $bFactory);

$a1 = $container->Resolve(A::class);
$b1 = $container->Resolve(B::class);
$a2 = $container->Resolve(A::class);
$b2 = $container->Resolve(B::class);

$a1->Func(); // A
$b1->Func(); // B

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