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

    private array $registry; //[クラス名 => [ファクトリー関数, シングルトンフラグ] の連想配列
    private array $instances; //[クラス名 => インスタンス] の連想配列、シングルトンモードで使用する。
    private const FACTORY_METHOD_INDEX = 0;
    private const SINGLETON_FLAG_INDEX = 1;

    private function __construct() {
        $this->registry = [];
        $this->instances = [];
    }

    public function register(string $className, bool $isSingleton = false, ?\Closure $factory = null): void {
        // #region ファクトリー関数のシグネチャバリデーション
        if ($factory !== null) {
            $refFunc = new \ReflectionFunction($factory);
            $parameters = $refFunc->getParameters();
            $returnType = $refFunc->getReturnType();
            
            // ファクトリー関数の引数バリデーション
            // 引数が1つ、かつ型ヒントが DiContainer であるか
            $hasValidParam = count($parameters) === 1 
                && $parameters[0]->getType() !== null 
                && !$parameters[0]->getType()->isBuiltin() 
                && $parameters[0]->getType()->getName() === self::class;

            // ファクトリー関数の戻り値バリデーション
            // 戻り値の型が指定されており、かつ登録する className と一致するか
            $hasValidReturn = $returnType !== null 
                && !$returnType->isBuiltin() 
                && $returnType->getName() === $className;

            $isValid = $hasValidParam && $hasValidReturn;

            if (!$isValid) {
                throw new \Exception("
                登録しようとしているファクトリー関数のシグネチャは
                - 引数が1つで、型ヒントが DiContainer であること
                - 戻り値の型が指定されており、かつ登録する className と一致すること
                である必要があります");
            }
        }
        // #endregion

        $this->registry[$className] = [$factory, $isSingleton];
        $this->instances[$className] = null;
    }

    public function resolve(string $className) {

        $isExists = array_key_exists($className, $this->registry);
        if ($isExists == false) throw new \Exception("登録されていないクラス名である{$className}が参照されました。");

        if ($this-> registry[$className][self::SINGLETON_FLAG_INDEX] === true) {
            if ($this->instances[$className] == null) {
                $this->instances[$className] = $this->resolveTransient($className);
            }
            return $this->instances[$className];
        } else {
            return $this->resolveTransient($className);
        }
    }

    private function resolveTransient(string $className) {
        $result = null;
        if ($this->registry[$className][self::FACTORY_METHOD_INDEX] === null) {
            // ファクトリー関数が登録されていない場合、コンストラクタを解析して実体化
            $refClass = new \ReflectionClass($className);
            $refMethod = $refClass->getConstructor();

            if ($refMethod === null) {
                $result = $refClass->newInstance();
            } else {
                $result = $this->resolveFromConstructor($className);
            }
        } else {
            // ファクトリー関数が登録されている場合、ファクトリー関数を使用して実体化
            $result = $this->resolveFromFactory($className);
        }
        return $result;
    }

    private function resolveSingleton(string $className) {
        $result = $this->instances[$className];
        if ($result === null) {
            throw new \Exception("[内部エラー]$this->instances連想配列で存在しないキーが指定されました。");
        }
    }

    // 指定したクラス名のクラスのコンストラクタを解析し、依存関係を解決してインスタンス化する
    private function resolveFromConstructor(string $className) {
        $refClass = new \ReflectionClass($className);
        $refMethod = $refClass->getConstructor();

        $result = null;
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

        return $result;
    }

    private function resolveFromFactory(string $className) {
        $factory = $this->registry[$className][self::FACTORY_METHOD_INDEX];
        return $factory($this);
    }
}