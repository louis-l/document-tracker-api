<?php

namespace App\Jobs;

use App\Models\User;
use App\Notifications\DocumentExpiringSoonNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

/**
 * This job will query all users that have expiring soon documents and send notifications to them.
 */
class SendExpiringSoonDocumentNotificationsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $now = now();
        // TODO: Should make this configurable
        $threshold = now()->addDays(7);

        // TODO: Assume we only need to send documents that are expiring soon, not the ones that already expired.
        User::query()
            ->withWhereHas('documents', fn (Builder $query) => $query
                ->whereNotNull('expires_at')
                ->whereBetween('expires_at', [$now, $threshold])
                ->whereNull('archived_at')
            )
            ->chunkById(100, function ($users) {
                foreach ($users as $user) {
                    $user->notify(
                        new DocumentExpiringSoonNotification($user->documents)
                    );
                }
            });
    }
}
