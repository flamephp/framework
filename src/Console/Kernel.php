<?php

declare(strict_types=1);

namespace Flame\Console;

use Flame\Config\Config;
use Flame\Facade\DB;
use Phinx\Console\Command;
use Symfony\Component\Console\Application;

class Kernel extends Application
{
    /**
     * Initialize the console application.
     */
    public function __construct()
    {
        parent::__construct('Console Tool.', '1.0');

        Config::init();

        DB::setConfig(Config::get('database'));

        $this->addCommands([
            new Command\Create(),
            new Command\Migrate(),
            new Command\Rollback(),
            new Command\Status(),
            new Command\SeedCreate(),
            new Command\SeedRun(),
        ]);

        // Load commands
        $this->registerCommands(__DIR__.'/Commands/*Command.php');
    }

    public function registerCommands(string $path, string $namespace = 'Flame'): void
    {
        $pattern = '/(Console\/Commands\/.+?Command)\.php/';
        foreach (glob($path) as $file) {
            preg_match($pattern, str_replace('\\', '/', $file), $matches);
            if (isset($matches[1])) {
                $command = $namespace.'\\'.str_replace('/', '\\', $matches[1]);
                $this->add(new $command());
            }
        }
    }
}
