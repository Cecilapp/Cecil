<?php
namespace PHPoole\Command;

use PHPoole\Command\AbstractCommand;
use PHPoole\PHPoole;
use PHPoole\Util;

class Serve extends AbstractCommand
{
    public function processCommand()
    {
        if (!is_file(sprintf('%s/%s/router.php', $this->_path, PHPoole::PHPOOLE_DIRNAME))) {
            $this->wlError('Router not found');
            exit(2);
        }
        $this->wlAnnonce(sprintf("Start server http://%s:%d", 'localhost', '8000'));
        if (Util::isWindows()) {
            $command = sprintf(
                'START php -S %s:%d -t %s %s > nul',
                'localhost',
                '8000',
                $this->_path,
                sprintf('%s/%s/router.php', $this->_path, PHPoole::PHPOOLE_DIRNAME)
            );
        }
        else {
            echo 'Ctrl-C to stop it.' . PHP_EOL;
            $command = sprintf(
                'php -S %s:%d -t %s %s >/dev/null',
                'localhost',
                '8000',
                $this->_path,
                sprintf('%s/%s/router.php', $this->_path, PHPoole::PHPOOLE_DIRNAME)
            );
        }
        exec($command);
    }
}