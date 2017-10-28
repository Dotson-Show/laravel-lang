<?php

/*
 * This file is part of the overtrue/laravel-lang.
 *
 * (c) overtrue <i@overtrue.me>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Overtrue\LaravelLang\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Process\Process;

class Publish extends Command
{
    /**
     * @var string
     */
    protected $signature = 'lang:publish
                            {locales=all : Comma-separated list of, eg: zh_CN,tk,th}
                            {--force : override existing files.}';

    /**
     * @var string
     */
    protected $description = 'publish language files to resources directory.';

    /**
     * @var bool
     */
    protected $inLumen = false;

    /**
     * Publish constructor.
     */
    public function __construct()
    {
        parent::__construct();
        $this->inLumen = $this->laravel instanceof \Laravel\Lumen\Application;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $locale = $this->argument('locales');
        $force = $this->option('force') ? 'f' : 'n';

        $sourcePath = base_path('vendor/caouecs/laravel-lang/src');
        $targetPath = base_path('resources/lang/');

        if (!is_dir($targetPath) || !is_writable($targetPath)) {
            return $this->error('The lang path "resources/lang/" does not exist or not writable.');
        }

        $files = [];
        $published = [];
        $copyEnFiles = false;

        if ($locale == 'all') {
            $files = [$sourcePath.'/*'];
            $message = 'all';
            $copyEnFiles = true;
        } else {
            foreach (explode(',', $locale) as $filename) {
                if ($locale === 'en') {
                    $copyEnFiles = true;
                    continue;
                }
                $file = $sourcePath.'/'.trim($filename);

                if (!file_exists($file)) {
                    $this->error("lang '$filename' not found.");

                    continue;
                }

                $published[] = $filename;
                $files[] = $file;
            }

            if (empty($files)) {
                return;
            }

            $message = json_encode($published);
        }

        if ($this->inLumen && $copyEnFiles) {
            $files[] = base_path('vendor/laravel/lumen-framework/resources/lang/en');
        }

        $files = implode(' ', $files);
        $process = new Process("cp -r{$force} $files $targetPath");

        $process->run(function ($type, $buffer) {
            if (Process::ERR === $type) {
                return $this->error(trim($buffer));
            }
        });

        $type = ($force == 'f') ? 'overwrite' : 'no overwrite';

        $this->info("published languages <comment>({$type})</comment>: {$message}.");
    }
}
