<?php

namespace Marks\Pusher\VCWrappers;

use Symfony\Component\Console\Command\Command;
use Marks\Pusher\BaseCommand;

class Subversion extends VCWrapper
{

    function __construct(Command $command, array $project)
    {
        $this->command = $command;
        $this->project = $project;
    }

    public function commit($message)
    {
        $this->sync();
        $message = str_replace('"', '\\"', $message);
        $this->command->exec("cd {$this->project['directory']} && svn up", true);
        $this->command->exec("cd {$this->project['directory']} && svn commit -m \"{$message}\"", true);
    }

    public function deploy()
    {
        $remote_directory = $this->project['remote']['directory'];
        $remote_command = "cd '{$remote_directory}' && svn up --ignore-externals";
        return $remote_command;
    }

    /**
     * Synchronize the SVN project (add and remove new and deleted files).
     *
     * @return void
     */
    protected function sync()
    {
        $this->command->log('Synchronizing SVN Files', 'white', BaseCommand::DEBUG_VERBOSE);
        $status_results = $this->command->exec('svn status ' . $this->project['directory'], true, false, true);

        $results = array(
            'added' => array(),
            'deleted' => array()
        );

        foreach ($status_results as $result) {

            // Get the matches.
            $matches = array();
            preg_match("/^.* /", $result, $matches);
            $filename = preg_replace("/^.* /u", '', $result);

            // Get the status code. Usually the first match.
            if (count($matches) <= 0) continue;
            $status_code = trim($matches[0]);

            // Fail if the status code is more than 1 character long.
            if (strlen($status_code) > 1) continue;

            // Apply the file to the various parts.
            if ($status_code == '?') {
                $results['added'][] = $filename;
            } else if ($status_code == '!') {
                $results['deleted'][] = $filename;
            }

        }

        // Now, tell svn to add or delete those files.
        foreach ($results['added'] as $toAdd) {
            $this->command->exec('cd ' . $this->project['directory'] . ' && svn add ' . $toAdd, false);
        }
        foreach ($results['deleted'] as $toDelete) {
            $this->command->exec('cd ' . $this->project['directory'] . ' && svn remove ' . $toDelete, false);
        }

        // Log the result if there is one to log.
        if (count($results['added']) > 0 || count($results['deleted']) > 0) {
            $this->command->log('Added ' . count($results['added']) .
                ' files and deleted ' . count($results['deleted']) . ' files.');
        }
    }

}
