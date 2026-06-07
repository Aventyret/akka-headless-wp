<?php
namespace Akka;

class Env
{
    /**
     * Validates that a set of env values are present and not placeholder
     * values. A placeholder is a value that equals its variable name
     * (e.g. AKKA_X='AKKA_X', a template line copied without filling in).
     *
     * In admin context the failure is rendered as an admin notice (so the
     * plugin can still load and the user can act on it). In other contexts
     * it is logged via error_log so the cms doesn't take the whole site
     * down on a missing optional caller.
     *
     * @param string[] $names
     * @return string[] List of error messages (empty when all OK).
     */
    public static function require_env(array $names): array
    {
        $errors = [];
        foreach ($names as $name) {
            $value = getenv($name);
            if ($value === false || $value === '') {
                $errors[] = "$name: required, but not set";
                continue;
            }
            if ($value === $name) {
                $errors[] = "$name: value equals variable name (placeholder)";
            }
        }
        return $errors;
    }

    /**
     * Boot-time validator. Runs the required-env check and surfaces
     * failures as an admin notice. Does NOT block plugin load — that
     * would lock the user out of the admin where they need to fix it.
     */
    public static function boot_validate(): void
    {
        $errors = self::require_env([
            'AKKA_WWW_PROXY_HEADER_NAME',
            'AKKA_WWW_PROXY_HEADER_SECRET',
            'AKKA_DRAFT_COOKIE_SECRET',
            'AKKA_FRONTEND_URL',
            'AKKA_FRONTEND_URL_INTERNAL',
            'AKKA_CMS_COOKIE_PATH',
        ]);
        if (empty($errors)) {
            return;
        }
        error_log('Akka env validation: ' . implode('; ', $errors));
        add_action('admin_notices', function () use ($errors) {
            echo '<div class="notice notice-error"><p><strong>Akka env validation failed:</strong></p><ul>';
            foreach ($errors as $err) {
                echo '<li><code>' . esc_html($err) . '</code></li>';
            }
            echo '</ul><p>See <code>akka-headless-wp/README.md</code> or the package <code>.env.example</code> in <code>@aventyret/akka-headless-wp</code>.</p></div>';
        });
    }
}
