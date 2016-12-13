<?php

namespace Give2Peer\Give2PeerBundle\Composer;

use Composer\Script\Event; // why is this not found by PHPStorm?

/**
 * Our very own composer event handler, because I'm strong-headed sometimes.
 */
class ScriptHandler
{
    /**
     * Composer variables are declared static so that an event could update
     * a composer.json and set new options, making them immediately available
     * to forthcoming listeners.
     */
    protected static $options = array(
        'symfony-bin-dir' => 'bin',
    );

    /**
     * Replace the default behat runner by our own, to solve some issues.
     * I don't want to have to define a phpunit.xml ; why should I ?
     *
     * @param Event $event
     */
    public static function replaceBehatRunner(Event $event)
    {
        $options = static::getOptions($event);
        $binDir = $options['symfony-bin-dir'];

        if ( ! is_dir($binDir)) {
            mkdir($binDir);
        }

        $rootDir = getcwd();
        $behat = $binDir . DIRECTORY_SEPARATOR . 'behat';
        $vanilla = $behat . '_original';
        $script = 'script' . DIRECTORY_SEPARATOR . 'behat';

        if (is_file($behat) || is_link($behat)) {
            // Don't overwrite the vanilla if it already exists
            if ( (! is_file($vanilla)) && (! is_link($vanilla))) {
                if ( ! rename($behat, $vanilla)) {
                    throw new \RuntimeException("Unable to backup behat at '${behat}'.");
                }
            } else {
                unlink($behat);
            }
        }
        if ( ! symlink($rootDir . DIRECTORY_SEPARATOR . $script, $behat)) {
            throw new \RuntimeException("Failed to symlink the new behat runner from '${script}' to '${behat}'.");
        }

    }

    protected static function getOptions(Event $event)
    {
        $options = array_merge(static::$options, $event->getComposer()->getPackage()->getExtra());

        return $options;
    }
}
