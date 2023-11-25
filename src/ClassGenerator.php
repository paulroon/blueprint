<?php

namespace HappyCode\Blueprint;

use HappyCode\Blueprint\Error\BlueprintError;

readonly class ClassGenerator
{

    public function __construct(private string $namespace, private string $className)
    {}

    /**
     * @throws BlueprintError
     */
    public static function ClassFromModel(Model $model): string
    {
        $namespace = $model->getUseNamespace();
        $className = sprintf("%sModel", $model->getModelName());
        $fqClassName = sprintf("%s\%s", $namespace, $className);

        if (class_exists($fqClassName)) {
            return $fqClassName;
        }

        $installDir = static::NamespaceToInstallDir($namespace);

        // Make the file
        static::EnsureDir($installDir);
        $filePath = sprintf("%s/%s.php", $installDir, $className);
        if (false !== (new static($namespace, $className))->buildClassCode($model, $filePath)) {
            require_once $filePath;
            return $fqClassName;
        }

        throw new BlueprintError(sprintf("Failed to save class[%s] to path[%s]", $fqClassName, $filePath));

    }

    public static function EnsureDir(string $dir): void
    {
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
    }

    public function buildClassCode(Model $model, string $fileLocation): false|int
    {
        $classCode = "<?php\n\n";
        $classCode .= "namespace {$this->namespace};\n\n";
        $classCode .= "use HappyCode\Blueprint\ModelData;\n\n";
        $classCode .= "/**\n";
        /**
         * @var  $field
         * @var Type $type
         */
        foreach ($model->getDefinition() as $field => $type) {
            $getterName = sprintf("get%s", ucfirst($field));
            $typeName = $type->getReferencableTypeName();
            $classCode .= " * @method {$typeName} {$getterName}()\n";
        }
        $classCode .= " */\n";
        $classCode .= "class {$this->className} extends ModelData {}\n\n";

        return file_put_contents($fileLocation, $classCode);
    }

    /**
     * @throws BlueprintError
     */
    private static function NamespaceToInstallDir(string $namespace): string
    {
        $namespace = rtrim($namespace, '\\');
        $vendorLocation = implode(DIRECTORY_SEPARATOR, ['vendor', 'happycode', 'blueprint', 'src']);
        $appRoot = dirname(__DIR__, count(explode(DIRECTORY_SEPARATOR, $vendorLocation)));
        $autoloadPath = $appRoot . DIRECTORY_SEPARATOR . implode(DIRECTORY_SEPARATOR, ['vendor', 'composer', 'autoload_psr4.php']);
print_r($autoloadPath);
        $autoload_psr4 = require $autoloadPath;
        if (!is_array($autoload_psr4)) {
            throw new BlueprintError('Invalid PSR-4 autoload configuration.');
        }

        foreach ($autoload_psr4 as $namespacePrefix => $paths) {
            if (str_starts_with($namespace, $namespacePrefix)) {
                foreach ($paths as $path) {
                    return sprintf("%s%s%s",
                        $path,
                        DIRECTORY_SEPARATOR,
                        str_replace('\\',
                            DIRECTORY_SEPARATOR,
                            str_replace($namespacePrefix, '', $namespace)
                        )
                    );
                }
            }
        }

        // Nothing matching from the Autoloader - apply to app root
        return sprintf("%s%s%s",
            $appRoot,
            DIRECTORY_SEPARATOR,
            str_replace("\\", DIRECTORY_SEPARATOR,
                $namespace
            )
        );

    }

}