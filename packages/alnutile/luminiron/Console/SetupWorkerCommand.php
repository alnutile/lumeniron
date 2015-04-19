<?php namespace LuminIron\Console;


use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class SetupWorkerCommand extends Command {

    protected $name = 'luminiron:setup';
    protected $description = 'Sets up the needed worker folder for you and other minor tweaks';

    public function handle()
    {
        $from   = __DIR__ . '/../workers';
        $to     = base_path('workers');
        $this->info($from);
        $this->info($to);
        File::copyDirectory($from, $to);
        $this->info("Copying workers directory to app root");
    }
}