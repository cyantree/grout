<?php
namespace Cyantree\Grout\Composer;
use Composer\Script\Event;
use Cyantree\Grout\Tools\StringTools;

class Commands
{
    public static function onProjectCreated(Event $e)
    {
        $io = $e->getIO();

//        return;

        $folder = 'modules/AppModule/';

        if (!is_dir($folder)) {
            $io->write("Couldn't find AppModule. Configuration will be stopped.");
            return;
        }

        $io->write('Configurating AppBaseConfig.php');
        $file = $folder . 'Configs/AppBaseConfig.php';
        $content = file_get_contents($file);

        $content = str_replace('###ACCESS_KEY###', StringTools::random(32), $content);

        file_put_contents($file, $content);

        $io->write('Configurating AppModule.php');
        $file = $folder . 'AppModule.php';
        $content = file_get_contents($file);

        $content = str_replace(array('###AUTH_USER###', '###AUTH_PASS###'), array('console_' . mt_rand(1000,9999), StringTools::random(16)), $content);

        file_put_contents($file, $content);

        $io->write('Your grout application has been configured.');
    }
}