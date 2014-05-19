<?php
namespace Cyantree\Grout\Tools;

class FileTools
{
    public static function listDirectory($directory, $includeDirectories = true, $includeFiles = true, $absolutePaths = false, $recursive = true)
    {
        $directory = str_replace('\\', '/', $directory);

        $items = array();

        if (!is_dir($directory)) {
            return null;
        }

        $directories = array('');

        while (($dir = array_pop($directories)) !== null) {
            $contents = scandir($directory . $dir);

            foreach ($contents as $content) {
                if ($content == '.' || $content == '..') {
                    continue;
                }

                $path = $dir . $content;
                $isDir = is_dir($directory . $path);

                if ($isDir) {
                    $path .= '/';

                    if ($recursive) {
                        $directories[] = $path;
                    }

                    if ($includeDirectories) {
                        if ($absolutePaths) {
                            $items[] = $directory . $path;

                        } else {
                            $items[] = $path;
                        }
                    }

                } else {
                    if ($includeFiles) {
                        if ($absolutePaths) {
                            $items[] = $directory . $path;

                        } else {
                            $items[] = $path;
                        }
                    }
                }
            }
        }

        return $items;
    }

    public static function deleteContents($directory, $excludes = null, $includes = null)
    {
        $directory = str_replace('\\', '/', $directory);

        if (!is_dir($directory)) {
            return;
        }

        $directories = array('');

        $emptyDirectories = array();

        while (($dir = array_pop($directories)) !== null) {
            $contents = scandir($directory . $dir);

            $ignoredDirectoryContent = false;

            foreach ($contents as $content) {
                if ($content == '.' || $content == '..') {
                    continue;
                }

                $path = $dir . $content;
                $isDir = is_dir($directory . $path);
                if ($isDir) {
                    $path .= '/';
                }

                $ignorePath = false;

                if ($excludes !== null) {
                    foreach ($excludes as $exclude) {
                        if ((substr($exclude, 0, 1) == '@' && preg_match($exclude, $path)) || $exclude == $path) {
                            $ignorePath = true;
                            break;
                        }
                    }
                }

                if ($ignorePath && $includes !== null) {
                    foreach ($includes as $include) {
                        if ((substr($include, 0, 1) == '@' && preg_match($include, $path)) || $include == $path) {
                            $ignorePath = false;
                            break;
                        }
                    }
                }

                if ($ignorePath) {
                    $ignoredDirectoryContent = true;
                    continue;
                }

                if ($isDir) {
                    $directories[] = $path;

                } else if (is_file($directory . $path)) {
                    unlink($directory . $path);
                }
            }

            if (!$ignoredDirectoryContent && $dir !== '') {
                $emptyDirectories[] = $directory . $dir;
            }
        }

        while (($directory = array_pop($emptyDirectories)) !== null) {
            rmdir($directory);
        }
    }

    public static function deleteDirectory($directory)
    {
        $directory = str_replace('\\', '/', $directory);
        if(substr($directory, strlen($directory) - 1) != '/'){
            $directory .= '/';
        }

        if (!is_dir($directory)) {
            return;
        }

        $directories = array($directory);
        $deleteDirectories = array($directory);

        while ($directory = array_pop($directories)) {
            $contents = scandir($directory);
            foreach ($contents as $content) {
                if ($content == '.' || $content == '..') continue;

                if (is_file($directory . $content)) {
                    unlink($directory . $content);
                } elseif (is_dir($directory . $content)) {
                    $directories[] = $directory . $content . '/';
                    $deleteDirectories[] = $directory . $content . '/';
                }
            }
        }

        while ($directory = array_pop($deleteDirectories)) {
            rmdir($directory);
        }
    }

    public static function createDirectory($directory, $permission = 0777, $testForExistence = true)
    {
        $directory = str_replace('\\', '/', $directory);

        if ($testForExistence) {
            if (file_exists($directory)) {
                return;
            }
        }

        mkdir($directory, $permission, true);
    }

    public static function copyDirectory($source, $target, $excludes = null, $includes = null, $keepTimestamps = false)
    {
        $directories = array('');

        $source = str_replace('\\', '/', $source);
        $target = str_replace('\\', '/', $target);

        if (!is_dir($source)) {
            return;
        }

        if (!is_dir($target)) mkdir($target, 0777, true);

        while (($directory = array_pop($directories)) !== null) {
            $contents = scandir($source . $directory);

            foreach ($contents as $content) {
                if ($content == '.' || $content == '..') continue;

                $path = $directory . $content;
                $isDir = is_dir($source . $path);
                if ($isDir) $path .= '/';

                $ignorePath = false;

                if ($excludes !== null) {
                    foreach ($excludes as $exclude) {
                        if ((substr($exclude, 0, 1) == '@' && preg_match($exclude, '/' . $path)) || $exclude == '/' . $path) {
                            $ignorePath = true;
                            break;
                        }
                    }
                }

                if ($ignorePath && $includes !== null) {
                    foreach ($includes as $include) {
                        if ((substr($include, 0, 1) == '@' && preg_match($include, '/' . $path)) || $include == '/' . $path) {
                            $ignorePath = false;
                            break;
                        }
                    }
                }

                if ($ignorePath) continue;

                if ($isDir) {
                    $directories[] = $path;

                    if (!is_dir($target . $path)) {
                        mkdir($target . $path);
                        if ($keepTimestamps) touch($target . $path, filemtime($source . $path));
                    }
                } else if (is_file($source . $path)) {
                    copy($source . $path, $target . $path);
                    if ($keepTimestamps) touch($target . $path, filemtime($source . $path));
                }
            }
        }
    }

    public static function replaceContent($file, $searches, $replaces, $eReg = false)
    {
        $content = file_get_contents($file);

        if ($eReg) $content = preg_replace($searches, $replaces, $content);
        else $content = str_replace($searches, $replaces, $content);

        file_put_contents($file, $content);
    }

    public static function replaceSection($file, $start, $end, $replaceWith, $trimStart = false, $trimEnd = false)
    {
        file_put_contents($file, StringTools::replaceSection(file_get_contents($file), $start, $end, $replaceWith, $trimStart, $trimEnd));
    }

    public static function createUniqueFilename($prefix, $extension = '', $randomChars = 32, $onlyReturnRandomizedPart = false, $charList = null)
    {
        do {
            $random = StringTools::random($randomChars, $charList);
            $file = $prefix . $random . $extension;
            $exists = file_exists($file);
        } while ($exists);

        if ($onlyReturnRandomizedPart) return $random;
        return $file;
    }

    public static function parsePhpFile($file, $data = null, &$out = null)
    {
        ob_start();
        include($file);

        return ob_get_clean();
    }

    public static function deleteFile($file)
    {
        $file = str_replace('\\', '/', $file);
        if (is_writable($file)) unlink($file);
    }
}