<?php

require_once __DIR__ . '/SmallDiContainer.php';

class A{
    public function func() : void {
        echo "A\n";
    }
}

class B{
    private A $_a;

    public function __construct(A $a) {
        $this->_a = $a;
    }

    public function func() : void {
        echo "B\n";
    }
}

$aFactory = function(\SmallDiContainer\DiContainer $container) : A {
    return new A();
};

$bFactory = function(\SmallDiContainer\DiContainer $container) : B {
    $a = $container->resolve(A::class);
    return new B($a);
};

$container = \SmallDiContainer\DiContainer::getInstance();

// ファクトリーメソッドを登録する場合ture
if (true) {
    $container->register(A::class, $aFactory);
    $container->register(B::class, $bFactory);
} else {
    $container->register(A::class);
    $container->register(B::class);
}

$a = $container->resolve(A::class);
$b = $container->resolve(B::class);

$a->func(); // A
$b->func(); // B

echo "Hello, World!";