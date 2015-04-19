<?php
/**
 * Created by PhpStorm.
 * User: alfrednutile
 * Date: 4/14/15
 * Time: 2:03 PM
 */

namespace LuminIron\Console;


use Illuminate\Console\Command;
use Illuminate\Queue\IronQueue;
use Illuminate\Support\Facades\File;
use IronMQ;
use RuntimeException;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Finder\Finder;

class UploadWorker extends Command{

    protected $name = 'luminiron:upload';
    protected $description = 'Uploads worker to target queue';

    /**
     * @var \IronWorker;
     */
    protected $iron_worker;
    protected $exec_worker_file_name;
    protected $worker_name;
    protected $upload_all;
    protected $workers_dir;
    protected $workers;
    protected $iron_worker_name;
    protected $iron_worker_name_no_extension;
    protected $worker_file_name;
    protected $subscriber_url;
    protected $token;
    protected $project_id;

    /**
     * @var IronQueue
     */
    protected $iron;
    protected $worker_params = [
        'stack' => 'php-5.6',
        'max_concurrency' => 100,
    ];

    public function handle()
    {

        $this->token        = env('IRON_TOKEN');
        $this->project_id   = env('IRON_PROJECT_ID');
        $this->worker_name = $this->option('worker_name');
        $this->exec_worker_file_name = $this->option('exec_worker_file_name');

        $this->info("Do something");

        $this->setIron();
        $this->setUpWorker();
        $this->uploadWorker();
    }

    protected function uploadWorker()
    {
        if($this->upload_all)
        {
            foreach ($this->workers as $worker_file_name) {
                $this->iron_worker_name = $this->remove_extension($worker_file_name->getFilename());
                $this->worker_file_name = $worker_file_name->getFilename();
                $this->subscriber_url = "ironworker:///" . $this->iron_worker_name;
                $this->upload_worker($this->iron_worker_name, $this->worker_file_name);
                if ($this->need_to_update_queue($this->iron_worker_name)) {
                    $iron = $this->getIron();
                    $this->update_queue($iron, $this->iron_worker_name);
                }
            }
            $this->show_workers_queues_list($this->workers);


        }
        else
        {

        }
    }

    protected function upload_worker($worker_name, $worker_file_name)
    {
        $this->info("<info>Starting to upload <comment>$worker_name</comment> worker</info>");
        try
        {
            $this->iron_worker->upload(
                $this->getWorkersDir() . '/' . $worker_file_name,
                $worker_name,
                $this->worker_params);
            $this->info("<info>Worker <comment>$worker_name</comment> uploaded</info>" . PHP_EOL);
        }
        catch(\Exception $e)
        {
            $message = sprintf("Error uploading worker %s", $e->getMessage());
            $this->error($message);
            throw new \Exception($message);
        }
    }

    protected function show_workers_queues_list($workers)
    {
        $this->line("<info>Your workers:</info>");
        foreach ($workers as $worker_file_name) {
            if (is_dir(getcwd() . '/' . $this->workers_dir . '/' . $worker_file_name))
                continue;
            $this->line('<comment>' . $this->remove_extension($worker_file_name) . '</comment>');
        }
    }

    /**
     * Get list of subscribers and compare it with current subscriber url
     *
     * @param $queue_name
     * @internal param $subscriber_url
     * @return bool
     */
    protected function need_to_update_queue($queue_name)
    {
        foreach ($this->getCurrentSubscribers($queue_name) as $subscriber) {
            if ($subscriber->url == $this->subscriber_url)
                return false;
        }
        return true;
    }

    /**
     * Update push queue
     *
     * @param $iron IronQueue
     * @param $queue_name
     */
    protected function update_queue($iron, $queue_name)
    {
        $this->info("<info>Creating or updating push queue <comment>$this->iron_worker_name</comment></info>");
        $iron->getIron()->updateQueue($queue_name, $this->getQueueOptions($queue_name));
        $this->line("<info>Push Queue <comment>$queue_name</comment> with subscriber <comment>$this->subscriber_url</comment> created or updated.</info>" . PHP_EOL);
    }

    /**
     * Get the queue options.
     *
     * @param $queue_name
     * @return array
     */
    protected function getQueueOptions($queue_name)
    {
        return array(
            'push_type' => $this->getPushType($queue_name), 'subscribers' => $this->getSubscriberList($queue_name)
        );
    }

    /**
     * Get the push type for the queue.
     *
     * @param $queue_name
     * @return string
     */
    protected function getPushType($queue_name)
    {
        if ($this->option('push_queue_type')) return $this->option('push_queue_type');

        try {
            return $this->getQueue($queue_name)->push_type;
        } catch (\Exception $e) {
            return 'multicast';
        }
    }

    /**
     * Get the current subscribers for the queue.
     *
     * @param $queue_name
     * @return array
     */
    protected function getSubscriberList($queue_name)
    {
        $subscribers = $this->getCurrentSubscribers($queue_name);
        $subscribers[] = array('url' => $this->subscriber_url);
        return $subscribers;
    }

    /**
     * Get the queue information from Iron.io.
     *
     * @param $queue_name
     * @return object
     */
    protected function getQueue($queue_name)
    {
        return $this->laravel['queue']->getIron()->getQueue($queue_name);
    }

    /**
     * Get the current subscriber list.
     *
     * @param $queue_name
     * @return array
     */
    protected function getCurrentSubscribers($queue_name)
    {
        try {
            return $this->getQueue($queue_name)->subscribers;
        } catch (\Exception $e) {
            return array();
        }
    }


    /**
     * Remove extension of the file
     *
     * @param $filename
     * @return mixed
     */
    protected function remove_extension($filename)
    {
        return preg_replace("/\\.[^.\\s]{3,4}$/", "", $filename);
    }


    protected function setUpWorker()
    {
        $this->iron_worker = new \IronWorker([
            'token' => $this->token,
            'project_id' => $this->project_id
        ]);

        $this->worker_params['project_id'] = $this->project_id;

        if ($this->worker_name == '*' && $this->exec_worker_file_name == '*') {
            $this->upload_all = true;
            $workers = new Finder();
            $workers = $workers->files()->depth('== 0')->name('*.php')->in($this->getWorkersDir());
            $this->workers = $workers;
        } else {
            $this->iron_worker_name = $this->option('worker_name');
            $this->subscriber_url = "ironworker:///" . $this->iron_worker_name;
        }
    }

    /**
     * @return IronQueue
     */
    public function getIron()
    {
        if($this->iron == null)
            $this->setIron();
        return $this->iron;
    }

    public function setIron($iron = null)
    {
        if($iron == null)
            $iron = $this->laravel['queue']->connection();

        if (!$iron instanceof IronQueue) {
            throw new RuntimeException("Iron.io based queue must be default.");
        }


        $iron->getIron()->setProjectId($this->project_id);
        $iron->getIron()->setToken($this->token);
        $this->iron = $iron;
    }

    protected function getOptions()
    {
        return array(
            array('worker_name', null, InputOption::VALUE_REQUIRED, 'Worker name.', null),
            array('exec_worker_file_name', null, InputOption::VALUE_REQUIRED, 'Execute worker file name.', null),
            array('push_queue_type', null, InputOption::VALUE_OPTIONAL, 'Type of the push queue.', null),
            array('max_concurrency', null, InputOption::VALUE_OPTIONAL, 'Max concurrency.', null),
        );
    }

    /**
     * @return mixed
     */
    public function getWorkersDir()
    {
        if($this->workers_dir == false)
            $this->setWorkersDir();
        return $this->workers_dir;
    }

    /**
     * @param mixed $workers_dir
     */
    public function setWorkersDir($workers_dir = false)
    {
        if($workers_dir == false)
            $workers_dir = base_path('workers');
        $this->workers_dir = $workers_dir;
    }
}