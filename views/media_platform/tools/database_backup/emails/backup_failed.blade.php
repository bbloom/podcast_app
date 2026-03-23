{{--
    views/database_backup/emails/backup_failed.blade.php

    Failure notification email sent to the admin when a backup run fails.

    Variables available:
      $failedAt     (Carbon)  — when the run started
      $filename     (string)  — the filename being attempted
      $errorMessage (string)  — the error from the step that failed
      $adminUrl     (string)  — link to the backup log admin page
--}}
<x-mail::message>

# Database Backup Failed

A scheduled database backup failed and requires your attention.

| | |
|---|---|
| **Time** | {{ $failedAt->format('Y-m-d H:i:s T') }} |
| **File** | `{{ $filename }}` |

**Error:**

> {{ $errorMessage }}

You can run the backup manually from the admin panel.

<x-mail::button :url="$adminUrl" color="red">
View Backup Log & Run Now
</x-mail::button>

To run the backup from the command line:

```
php artisan backup:database
```

Thanks,<br>
{{ config('app.name') }}

</x-mail::message>