<?php

namespace MediaPlatform\Digest\ContentSources\OutputDestinations\Services;

use MediaPlatform\Digest\ContentSources\OutputDestinations\Models\OutputDestination;
use League\Flysystem\Filesystem;
use League\Flysystem\PhpseclibV3\SftpAdapter;
use League\Flysystem\PhpseclibV3\SftpConnectionProvider;
use League\Flysystem\UnableToListContents;
use League\Flysystem\UnableToWriteFile;
use Throwable;

/**
 * SftpService — handles all SFTP operations.
 *
 * RESPONSIBILITIES
 * ────────────────
 * 1. Connection testing (used by the OutputDestination wizard)
 * 2. File upload (used by PublishDigest for webpage output)
 *
 * Both operations share the private `buildFilesystem()` helper to construct
 * a Flysystem Filesystem from either a wizard session array or a persisted
 * OutputDestination model.
 *
 * CREDENTIALS
 * ───────────
 * Passwords, private keys, and passphrases are stored encrypted in the database
 * (OutputDestination model uses Laravel's `encrypted` cast). By the time they
 * reach this service they are already decrypted plain-text strings.
 */
class SftpService
{
    // =========================================================================
    // Connection Testing (used by OutputDestination wizard)
    // =========================================================================

    /**
     * Test an SFTP connection using password authentication.
     * Returns ['success' => true] or ['success' => false, 'message' => '...', 'error_step' => int|null]
     */
    public function testWithPassword(
        string $host,
        int    $port,
        string $username,
        string $password,
        string $path = '/'
    ): array {
        return $this->attempt(
            SftpConnectionProvider::fromArray([
                'host'     => $host,
                'port'     => $port,
                'username' => $username,
                'password' => $password,
            ]),
            $path
        );
    }

    /**
     * Test an SFTP connection using SSH key authentication.
     * Returns ['success' => true] or ['success' => false, 'message' => '...', 'error_step' => int|null]
     */
    public function testWithSshKey(
        string  $host,
        int     $port,
        string  $username,
        string  $privateKey,
        ?string $passphrase = null,
        string  $path = '/'
    ): array {
        return $this->attempt(
            SftpConnectionProvider::fromArray([
                'host'        => $host,
                'port'        => $port,
                'username'    => $username,
                'privateKey'  => $privateKey,
                'passPhrase'  => $passphrase,
                'useAgent'    => false,
            ]),
            $path
        );
    }

    // =========================================================================
    // File Upload (used by PublishDigest for webpage output)
    // =========================================================================

    /**
     * Upload a string as a file to the remote SFTP server.
     *
     * Builds the Flysystem Filesystem from the persisted OutputDestination,
     * which has already-decrypted credentials via Laravel's encrypted cast.
     *
     * The $filename is relative to the destination's configured path.
     * Example: upload($dest, 'tech-digest-2026-03-13', '<html>...')
     *   → writes to /configured/path/tech-digest-2026-03-13
     *
     * Returns ['success' => true, 'path' => '/full/remote/path'] on success,
     * or ['success' => false, 'message' => '...'] on failure.
     */
    public function upload(OutputDestination $dest, string $filename, string $content): array
    {
        try {
            $filesystem = $this->buildFilesystemFromDestination($dest);

            $filenameWithExtension = $filename . '.html';

            $filesystem->write($filenameWithExtension, $content);

            return [
                'success' => true,
                'path'    => rtrim($dest->path, '/') . '/' . $filenameWithExtension,
            ];

        } catch (UnableToWriteFile $e) {
            return [
                'success' => false,
                'message' => 'Could not write file to the remote server: ' . $e->getMessage(),
            ];
        } catch (Throwable $e) {
            return $this->humanizeError($e->getMessage());
        }
    }

    // =========================================================================
    // Private Helpers
    // =========================================================================

    /**
     * Attempt to connect and list the remote path to confirm access.
     * Used by both testWithPassword() and testWithSshKey().
     */
    private function attempt(SftpConnectionProvider $provider, string $path): array
    {
        try {
            $adapter    = new SftpAdapter($provider, $path);
            $filesystem = new Filesystem($adapter);

            $filesystem->listContents($path)->toArray();

            return ['success' => true];

        } catch (UnableToListContents $e) {
            $reason = $e->getMessage();

            if (str_contains($reason, 'authenticate') || str_contains($reason, 'Authentication') || str_contains($reason, 'private key')) {
                return [
                    'success'    => false,
                    'message'    => 'Unable to authenticate using the provided private key. Please check your username and private key.',
                    'error_step' => 4,
                ];
            }

            return [
                'success'    => false,
                'message'    => 'Connected to the server but could not access the path. Please check the remote path.',
                'error_step' => 5,
            ];
        } catch (Throwable $e) {
            return $this->humanizeError($e->getMessage());
        }
    }

    /**
     * Build a Flysystem Filesystem from a persisted OutputDestination record.
     *
     * Decryption of password/private_key/passphrase is handled automatically
     * by the model's `encrypted` cast before this method receives them.
     *
     * @throws Throwable — callers should catch Throwable for unexpected connection errors
     */
    protected function buildFilesystemFromDestination(OutputDestination $dest): Filesystem
    {
        // Build the connection provider array based on auth type.
        $providerConfig = [
            'host'     => $dest->host,
            'port'     => $dest->port,
            'username' => $dest->username,
        ];

        if ($dest->auth_type === 'ssh_key') {
            // SSH key authentication — private_key and passphrase are decrypted
            // automatically by the model's encrypted cast.
            $providerConfig['privateKey']  = $dest->private_key;
            $providerConfig['passPhrase']  = $dest->passphrase;
            $providerConfig['useAgent']    = false;
        } else {
            // Password authentication — password is decrypted by the encrypted cast.
            $providerConfig['password'] = $dest->password;
        }

        $provider = SftpConnectionProvider::fromArray($providerConfig);

        // The configured path is the root of the Flysystem adapter — all file
        // operations (write, delete, list) will be relative to this path.
        $adapter = new SftpAdapter($provider, $dest->path ?? '/');

        return new Filesystem($adapter);
    }

    /**
     * Convert common SFTP error messages into a human-readable result array
     * including an error_step hint for the UI.
     *
     * error_step values map to wizard steps (post-renumbering):
     *   3    = host & port          (was 2 before type-selection step was inserted)
     *   5    = authentication       (was 4)
     *   null = unknown / show all links
     *
     * Note: path errors are handled inline in attempt() before this method is
     * called (they throw UnableToListContents, not a generic Throwable), so
     * there is no error_step for path here.
     */
    private function humanizeError(string $message): array
    {
        if (str_contains($message, 'Connection refused')) {
            return [
                'success'    => false,
                'message'    => 'Connection refused. Please check the host and port.',
                'error_step' => 3,
            ];
        }

        if (str_contains($message, 'No such host')) {
            return [
                'success'    => false,
                'message'    => 'Host not found. Please check the server address.',
                'error_step' => 3,
            ];
        }

        if (str_contains($message, 'Connection timed out')) {
            return [
                'success'    => false,
                'message'    => 'Connection timed out. The server may be unreachable.',
                'error_step' => 3,
            ];
        }

        if (str_contains($message, 'Authentication failed')) {
            return [
                'success'    => false,
                'message'    => 'Authentication failed. Please check your username and credentials.',
                'error_step' => 5,
            ];
        }

        if (str_contains($message, 'Permission denied')) {
            return [
                'success'    => false,
                'message'    => 'Permission denied. Please check your credentials and remote path.',
                'error_step' => 5,
            ];
        }

        return [
            'success'    => false,
            'message'    => 'Could not connect to the server. Please double check all your details and try again.',
            'error_step' => null,
        ];
    }
}