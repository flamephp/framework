<?php

declare(strict_types=1);

namespace Flame\Console\Commands;

use Flame\Facade\DB;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class GenModelCommand extends Command
{
    private array $ignoreTables = ['migrations'];

    public function __construct(string $name = null)
    {
        parent::__construct($name);
    }

    protected function configure(): void
    {
        $this->setName('gen:model')
            ->setDescription('Generate data access objects');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $database = env('DB_DATABASE', 'force');
        $tables = DB::query('show tables;');

        foreach ($tables as $row) {
            $tableName = implode('', $row);
            if (in_array($tableName, $this->ignoreTables)) {
                continue;
            }
            $className = parse_name($tableName, 1);
            $columns = $this->getTable($database, $tableName);

            $this->entityTpl($className, $columns);
            $this->modelTpl($tableName, $className, $columns);
        }

        return 1;
    }

    private function getTable($database, $tableName): array
    {
        $sql = "SELECT COLUMN_NAME,COLUMN_COMMENT FROM	information_schema.COLUMNS WHERE TABLE_SCHEMA = '{$database}' AND TABLE_NAME = '{$tableName}'";
        $result = DB::query($sql);

        $comments = [];
        foreach ($result as $row) {
            $comments[$row['COLUMN_NAME']] = $row['COLUMN_COMMENT'];
        }

        $sql = 'desc '.$tableName;
        $result = DB::query($sql);

        $columns = [];
        foreach ($result as $row) {
            $row['Comment'] = $comments[$row['Field']];
            $row['BaseType'] = $this->getFieldType($row['Type']);
            $columns[] = $row;
        }

        return $columns;
    }

    private function getFieldType($type): string
    {
        preg_match('/(\w+)\(/', $type, $m);
        $type = $m[1] ?? $type;
        $type = str_replace(' unsigned', '', $type);
        if (in_array($type, ['bit', 'int', 'bigint', 'mediumint', 'smallint', 'tinyint', 'enum'])) {
            $type = 'int';
        }
        if (in_array($type, ['varchar', 'char', 'text', 'mediumtext', 'longtext'])) {
            $type = 'string';
        }
        if (in_array($type, ['decimal'])) {
            $type = 'float';
        }
        if (in_array($type, ['date', 'datetime', 'timestamp', 'time'])) {
            $type = 'string';
        }

        return $type;
    }

    private function getSet($field, $type): string
    {
        $capitalName = parse_name($field, 1);

        return <<<EOF
    public function get{$capitalName}(): $type
    {
        return \$this->$field;
    }

    public function set{$capitalName}($type \${$field}): void
    {
        \$this->$field = \${$field};
    }
EOF;
    }

    private function modelTpl($tableName, $className, $columns): void
    {
        $softDelete = false;

        $fieldStr = '';
        foreach ($columns as $column) {
            $fieldStr .= str_pad(' ', 8)."'{$column['Field']}',\n";
            if ($column['Field'] === 'deleted_time') {
                $softDelete = true;
            }
        }

        $useSoftDelete = '';
        $deleteTime = '';
        if ($softDelete) {
            $useSoftDelete = "    use SoftDelete;\n\n";
            $deleteTime = "    /**
     * 软删除字段
     */
    protected string \$deleteTime = 'deleted_time';\n\n";
        }

        $content = <<<EOF
<?php

declare(strict_types=1);

namespace app\\model;

use think\model\concern\SoftDelete;

class {$className}Model extends CommonModel
{
$useSoftDelete
    /**
     * 设置表
     */
    protected \$name = '$tableName';

    /**
     * 是否需要自动写入时间戳 如果设置为字符串 则表示时间字段的类型.
     *
     * @var bool|string
     */
    protected \$autoWriteTimestamp = 'datetime';

    /**
     * 创建时间字段 false表示关闭.
     *
     * @var false|string
     */
    protected \$createTime = 'created_time';

    /**
     * 更新时间字段 false表示关闭.
     *
     * @var false|string
     */
    protected \$updateTime = 'updated_time';

$deleteTime
    /**
     * 设置字段
     */
    protected \$field = [
$fieldStr    ];

}

EOF;
        file_put_contents(app_path('model/'.$className.'Model.php'), $content);
    }

    private function entityTpl($className, $columns): void
    {
        $fields = "\n";
        foreach ($columns as $column) {
            if ($column['Field'] == 'default') {
                $column['Field'] = 'isDefault';
            }
            $fields .= "    #[OA\Property(property: '{$column['Field']}', description: '{$column['Comment']}', type: '{$column['BaseType']}')]\n";
            $fields .= '    private '.$column['BaseType'].' $'.parse_name($column['Field'], 1, false).";\n\n";
        }

        foreach ($columns as $column) {
            $fields .= $this->getSet(parse_name($column['Field'], 1, false), $column['BaseType'])."\n\n";
        }

        $content = <<<EOF
<?php

// Code generated by gen:model. DO NOT EDIT.

declare(strict_types=1);

namespace app\\entity;

use base\support\ArrayObject;
use OpenApi\Attributes as OA;

#[OA\Schema(schema: '{$className}Schema')]
class {$className}
{
    use ArrayObject;
{$fields}}
EOF;
        file_put_contents(app_path('entity/'.$className.'.php'), $content);
    }
}
