<?php
/**
 * Created by PhpStorm.
 * User: alfrednutile
 * Date: 4/14/15
 * Time: 2:03 PM
 */

namespace LuminIron\Console;


use Illuminate\Console\Command;

class RunWorker extends Command{

    protected $name = 'luminiron:run';
    protected $description = 'Runs a job for testing worker';

    public function handle()
    {

        $this->info("Do something");
    }
}