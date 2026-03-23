<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add WordPress-specific credential and publishing columns to output_destinations.
 *
 * These columns are only populated when type = 'wordpress'. All are nullable
 * so that existing SFTP destinations are unaffected.
 *
 * AUTHENTICATION
 * ──────────────
 * WordPress Application Passwords (introduced in WP 5.6) are used for REST API
 * authentication. The app password is stored encrypted. It is distinct from the
 * user's login password and can be revoked independently via the WP admin.
 * Reference: https://make.wordpress.org/core/2020/11/05/application-passwords-integration-guide/
 *
 * POST FIELDS
 * ───────────
 * - wordpress_post_status : 'publish', 'draft', or 'private' — controls visibility
 * - wordpress_category_ids: comma-separated WP category term IDs (e.g. "3,7")
 * - wordpress_tag_ids      : comma-separated WP tag term IDs (e.g. "12,45")
 *
 * Category and tag IDs are stored as strings here rather than normalised into
 * pivot tables, because they reference WordPress's own taxonomy structure, not
 * anything in this application's database.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('output_destinations', function (Blueprint $table) {

            // ── WordPress site URL ────────────────────────────────────────────
            // The root URL of the WordPress site, e.g. https://mysite.com
            // The REST API endpoint will be constructed as {url}/wp-json/wp/v2/posts
            $table->string('wordpress_url')
                ->nullable()
                ->comment('Root URL of the WordPress site e.g. https://mysite.com — REST API posts at {url}/wp-json/wp/v2/posts');

            // ── WordPress Application Password credentials ────────────────────
            // Username (the WP login username, not email) and Application Password.
            // Application Passwords are generated in WP Admin → Users → Edit User.
            $table->string('wordpress_username')
                ->nullable()
                ->comment('WordPress login username (not email) used with Application Password auth');

            $table->string('wordpress_app_password')
                ->nullable()
                ->comment('Encrypted WordPress Application Password — generated in WP Admin → Users → Edit User. NOT the login password.');

            // ── Post publishing settings ──────────────────────────────────────
            // Controls whether posts are immediately public, saved as drafts, or private.
            $table->string('wordpress_post_status')
                ->nullable()
                ->default('publish')
                ->comment('WP post status: publish, draft, or private. Controls visibility when the post is created.');

            // ── Taxonomy: categories ──────────────────────────────────────────
            // Comma-separated WordPress category term IDs. These are IDs from the
            // WP database (wp_terms.term_id), not slugs or names.
            // Example: "3,7" assigns the post to category IDs 3 and 7.
            $table->string('wordpress_category_ids')
                ->nullable()
                ->comment('Comma-separated WordPress category term IDs e.g. "3,7". Leave blank for no category assignment.');

            // ── Taxonomy: tags ────────────────────────────────────────────────
            // Comma-separated WordPress tag term IDs, same convention as categories.
            $table->string('wordpress_tag_ids')
                ->nullable()
                ->comment('Comma-separated WordPress tag term IDs e.g. "12,45". Leave blank for no tag assignment.');
        });
    }

    public function down(): void
    {
        Schema::table('output_destinations', function (Blueprint $table) {
            $table->dropColumn([
                'wordpress_url',
                'wordpress_username',
                'wordpress_app_password',
                'wordpress_post_status',
                'wordpress_category_ids',
                'wordpress_tag_ids',
            ]);
        });
    }
};