<?php

/**
 * Plugin Name: Akka CLI Lock MySQL
 * Description: WP-CLI-kommando som tar MySQL-lås innan ett underkommando körs.
 * Version: 0.1.0
 */

if (defined('WP_CLI') && WP_CLI) {
    class Akka_WPCLI_With_Lock
    {
        /**
         * Ta MySQL-lås och kör ett WP-CLI-subkommando.
         *
         * ## OPTIONS
         *
         * [--key=<key>]
         * : Namn på låset. Default: 'akka_lock'
         *
         * [--acquire-timeout=<seconds>]
         * : Max sekunder att vänta på låset. Default: 2
         *
         * [--cmd=<wp-subcommand>]
         * : Hela WP-CLI-kommandot att köra under lås (t.ex. "cron event run --due-now").
         *
         * ## EXAMPLES
         *
         *    # Via --cmd
         *    wp akka lock --cmd='cron event run --due-now --quiet'
         *
         */
        public function lock($args, $assoc_args)
        {
            global $wpdb;

            $key = $assoc_args['key'] ?? 'akka_lock';
            $cmd = $assoc_args['cmd'] ?? '--help';
            $wait = isset($assoc_args['acquire-timeout']) ? (int) $assoc_args['acquire-timeout'] : 2;

            if ($cmd === '') {
                WP_CLI::error('Saknar underkommando. Använd --cmd="…" eller pass-through efter --.');
            }

            error_log($cmd);
            $got = $wpdb->get_var($wpdb->prepare('SELECT GET_LOCK(%s, %d)', $key, $wait));
            if ($got === null) {
                WP_CLI::error('GET_LOCK returnerade NULL (kontrollera DB-anslutning/rättigheter).');
            }
            if ((int) $got !== 1) {
                WP_CLI::log("Lås upptaget: {$key} — avbryter körning.");
                return;
            }

            try {
                WP_CLI::log("Lås erhållet: {$key}");
                $result = WP_CLI::runcommand($cmd, [
                    'return' => 'all',
                    'launch' => true,
                    'exit_error' => false,
                ]);
                if ($result->return_code !== 0) {
                    WP_CLI::error("Underkommando misslyckades (exit {$result->return_code}).");
                }
                WP_CLI::success($result->stdout);
            } finally {
                $wpdb->get_var($wpdb->prepare('SELECT RELEASE_LOCK(%s)', $key));
            }
        }
    }

    WP_CLI::add_command('akka', 'Akka_WPCLI_With_Lock');
}
