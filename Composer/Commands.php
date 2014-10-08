<?php
namespace Cyantree\Grout\Composer;
use Composer\Script\Event;
use Cyantree\Grout\Tools\StringTools;

class Commands
{
    public static function onProjectCreated(Event $e)
    {
        $io = $e->getIO();

        $folder = 'modules/AppModule/';

        if (!is_dir($folder)) {
            $io->write("Couldn't find AppModule. Configuration will be stopped.");
            return;
        }

        $io->write('Configurating AppBaseConfig.php');
        $file = $folder . 'Configs/AppBaseConfig.php';
        $content = file_get_contents($file);

        $content = str_replace(
              array('###ACCESS_KEY###', '###ROOT_PASS###'),
              array(StringTools::random(32), StringTools::random(16)), $content);

        file_put_contents($file, $content);

        $io->write('Your grout application has been configured.');
    }
}