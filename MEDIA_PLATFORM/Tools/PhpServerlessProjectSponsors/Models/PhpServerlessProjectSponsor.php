<?php

namespace MediaPlatform\Tools\PhpServerlessProjectSponsors\Models;

use Database\Factories\Media_platform\Tools\PhpServerlessProjectSponsors\PhpServerlessProjectSponsorFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PhpServerlessProjectSponsor extends Model
{
    use HasFactory;

    // -------------------------------------------------------------------------
    // Table name — always explicit per conventions.
    // -------------------------------------------------------------------------
    protected $table = 'phpserverlessproject_sponsors';

    // -------------------------------------------------------------------------
    // Mass-assignable columns.
    // -------------------------------------------------------------------------
    protected $fillable = [
        'full_name',
        'image_url',
        'image_thumbnail_url',
        'profile_full',
        'profile_short',
        'link_to_sponsor_website',
        'email_address',
        'umbrella_sponsor',
        'basecamp_sponsor',
        'restream_sponsor',
        'former_sponsor',
        'internal_comment',
        'enabled',
    ];

    // -------------------------------------------------------------------------
    // Type casts.
    // -------------------------------------------------------------------------
    protected $casts = [
        'umbrella_sponsor' => 'boolean',
        'basecamp_sponsor' => 'boolean',
        'restream_sponsor' => 'boolean',
        'former_sponsor'   => 'boolean',
        'enabled'          => 'boolean',
    ];

    // -------------------------------------------------------------------------
    // Factory resolution — points to the non-standard factory path.
    // -------------------------------------------------------------------------
    protected static function newFactory(): PhpServerlessProjectSponsorFactory
    {
        return PhpServerlessProjectSponsorFactory::new();
    }
}