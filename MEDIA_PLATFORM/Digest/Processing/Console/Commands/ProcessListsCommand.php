<?php

namespace MediaPlatform\Digest\Processing\Console\Commands;

use MediaPlatform\Digest\ContentSources\Lists\Models\ListModel;
use MediaPlatform\Digest\Processing\Jobs\DispatchDueLists;
use MediaPlatform\Digest\Processing\Jobs\ProcessList;
use Illuminate\Console\Command;

class ProcessListsCommand extends Command
{
    protected $signature = 'processing:dispatch
                            {--list= : Process a specific list by ID (bypasses schedule check)}
                            {--sync : Run synchronously instead of dispatching to queue}';

    protected $description = 'Dispatch due lists for processing, or process a specific list';

    public function handle(): int
    {
        $listId = $this->option('list');

        if ($listId) {
            $list = ListModel::find($listId);

            if (! $list) {
                $this->error("List ID {$listId} not found.");
                return Command::FAILURE;
            }

            $this->info("Processing list: {$list->name} (ID {$list->id})...");

            if ($this->option('sync')) {
                dispatch_sync(new ProcessList($list));
            } else {
                ProcessList::dispatch($list);
                $this->info('ProcessList job dispatched to queue.');
            }
        } else {
            $this->info('Checking for due lists...');

            if ($this->option('sync')) {
                dispatch_sync(new DispatchDueLists());
            } else {
                DispatchDueLists::dispatch();
                $this->info('DispatchDueLists job dispatched to queue.');
            }
        }

        $this->info('Done.');
        return Command::SUCCESS;
    }
}
