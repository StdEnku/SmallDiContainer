<?php

/*
MIT License

Copyright (c) 2026 std::Enkū

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
SOFTWARE.
*/

namespace SmallDiContainer;

class DiContainer {
    // #region シングルトン
    static private ?DiContainer $instance = null;

    public static function getInstance(): self {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    // #endregion

    private array $registry; //[クラス名 => ファクトリーメソッド] の連想配列

    private function __construct() {
        $this->registry = [];
    }

    public function register(string $className, ?\Closure $factory = null): void {
        if ($factory !== null) {
            $refFunc = new \ReflectionFunction($factory);
            $parameters = $refFunc->getParameters();
            $returnType = $refFunc->getReturnType();
            
            // ファクトリーメソッドの引数バリデーション
            // 引数が1つ、かつ型ヒントが DiContainer であるか
            $hasValidParam = count($parameters) === 1 
                && $parameters[0]->getType() !== null 
                && !$parameters[0]->getType()->isBuiltin() 
                && $parameters[0]->getType()->getName() === self::class;

            // ファクトリーメソッドの戻り値バリデーション
            // 戻り値の型が指定されており、かつ登録する className と一致するか
            $hasValidReturn = $returnType !== null 
                && !$returnType->isBuiltin() 
                && $returnType->getName() === $className;

            $isValid = $hasValidParam && $hasValidReturn;

            if (!$isValid) {
                throw new \Exception("
                登録しようとしているファクトリーメソッドのシグネチャは
                - 引数が1つで、型ヒントが DiContainer であること
                - 戻り値の型が指定されており、かつ登録する className と一致すること
                である必要があります");
            }
        }

        $this->registry[$className] = $factory;
    }

    public function resolve(string $className) {

        $result = null;
        if ($this->registry[$className] === null) {
            $refClass = new \ReflectionClass($className);
            $refMethod = $refClass->getConstructor();

            if ($refMethod === null) {
                $result = $refClass->newInstance();
            } else {
                $params = $refMethod->getParameters();
                $args = [];

                foreach ($params as $param) {
                    $paramClass = $param->getType()->getName();
                    $args[] = $this->resolve($paramClass);
                }

                $result = $refClass->newInstanceArgs($args);
            }
        } else {
            $result = $this->registry[$className]($this);
        }

        return $result;
    }
}
