<?php
namespace Cyantree\Grout\Composer;
use Composer\Script\Event;
use Cyantree\Grout\Tools\StringTools;

class Commands
{
    public static function onProjectCreated(Event $e)
    {
        $io = $e->getIO();

        $folder = 'modules/BootstrapModule/Configs/';

        if(!is_dir($folder)){
            $io->write("Couldn't find BootstrapModule. Configuration will be stopped.");
            return;
        }

        $file = $folder.'BootstrapDefaultConfig.php';

        if(!is_file($file)){
            $io->write("Couldn't find default bootstrap configuration. Configuration will be stopped.");
            return;
        }

        $io->write('Configurating BootstrapDefaultConfig.php');
        $content = file_get_contents($file);


        $io->write('Updating internalAccessKey');
        $content = str_replace('ReplaceGroutInternalAccessKey', StringTools::random(32), $content);

        $io->write('Saving BootstrapDefaultConfig.php');
        file_put_contents($file, $content);

        $io->write('Your Grout application has been configured.');
    }
}