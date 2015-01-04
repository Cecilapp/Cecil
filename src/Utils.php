<?php
namespace PHPoole\Utils;

/**
 * PHPoole Utils
 */
class Utils
{
    /**
     * Recursively remove a directory
     *
     * @param string $dirname
     * @param boolean $followSymlinks
     * @return boolean
     */
    public static function RecursiveRmdir($dirname, $followSymlinks=false) {
        if (is_dir($dirname) && !is_link($dirname)) {
            if (!is_writable($dirname)) {
                throw new \Exception(sprintf('%s is not writable!', $dirname));
            }
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($dirname),
                \RecursiveIteratorIterator::CHILD_FIRST
            );
            while ($iterator->valid()) {
                if (!$iterator->isDot()) {
                    if (!$iterator->isWritable()) {
                        throw new \Exception(sprintf(
                            '%s is not writable!',
                            $iterator->getPathName()
                        ));
                    }
                    if ($iterator->isLink() && $followLinks === false) {
                        $iterator->next();
                    }
                    if ($iterator->isFile()) {
                        @unlink($iterator->getPathName());
                    }
                    elseif ($iterator->isDir()) {
                        @rmdir($iterator->getPathName());
                    }
                }
                $iterator->next();
            }
            unset($iterator);
     
            return @rmdir($dirname);
        }
        else {
            throw new \Exception(sprintf('%s does not exist!', $dirname));
        }
    }

    /**
     * Copy a dir, and all its content from source to dest
     */
    public static function RecursiveCopy($source, $dest) {
        if (!is_dir($dest)) {
            @mkdir($dest);
        }
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator(
                $source,
                \RecursiveDirectoryIterator::SKIP_DOTS
            ),
            \RecursiveIteratorIterator::SELF_FIRST
        );
        foreach ($iterator as $item) {
            if ($item->isDir()) {
                @mkdir($dest . DS . $iterator->getSubPathName());
            }
            else {
                @copy($item, $dest . DS . $iterator->getSubPathName());
            }
        }
    }

    /**
     * Execute git commands
     * 
     * @param string working directory
     * @param array git commands
     * @return void
     */
    public static function runGitCmd($wd, $commands)
    {
        $cwd = getcwd();
        chdir($wd);
        exec('git config core.autocrlf false');
        foreach ($commands as $cmd) {
            //printf("> git %s\n", $cmd);
            exec(sprintf('git %s', $cmd));
        }
        chdir($cwd);
    }

    public static function isWindows()
    {
        return strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
    }

    public static function slugify($string) {
        
        return md5($string);

        $separator = '-';
        $string = preg_replace('/
            [\x09\x0A\x0D\x20-\x7E]              # ASCII
            | [\xC2-\xDF][\x80-\xBF]             # non-overlong 2-byte
            |  \xE0[\xA0-\xBF][\x80-\xBF]        # excluding overlongs
            | [\xE1-\xEC\xEE\xEF][\x80-\xBF]{2}  # straight 3-byte
            |  \xED[\x80-\x9F][\x80-\xBF]        # excluding surrogates
            |  \xF0[\x90-\xBF][\x80-\xBF]{2}     # planes 1-3
            | [\xF1-\xF3][\x80-\xBF]{3}          # planes 4-15
            |  \xF4[\x80-\x8F][\x80-\xBF]{2}     # plane 16
            /', '', $string);
        // @see https://github.com/cocur/slugify/blob/master/src/Cocur/Slugify/Slugify.php
     
        // transliterate
        $string = iconv('utf-8', 'us-ascii//TRANSLIT', $string);
        // replace non letter or digits by seperator
        $string = preg_replace('#[^\\pL\d]+#u', $separator, $string);
        // trim
        $string = trim($string, $separator);
        // lowercase
        $string = (defined('MB_CASE_LOWER')) ? mb_strtolower($string) : strtolower($string);
        // remove unwanted characters
        $string = preg_replace('#[^-\w]+#', '', $string);

        return $string;
    }
}