<?php
namespace PHPoole\Command;

use PHPoole\Command\AbstractCommand;
use PHPoole\Api;
use PHPoole\Utils;

class Serve extends AbstractCommand
{
    public function processCommand()
    {
        if (!is_file(sprintf('%s/%s/router.php', $this->_path, Api::PHPOOLE_DIRNAME))) {
            $this->_console->write('Router not found');
            exit(2);
        }
        $this->_console->write(sprintf("Start server http://%s:%d", 'localhost', '8000'));
        if (Utils::isWindows()) {
            $command = sprintf(
                'START php -S %s:%d -t %s %s > nul',
                'localhost',
                '8000',
                $this->_path,
                sprintf('%s/%s/router.php', $this->_path, Api::PHPOOLE_DIRNAME)
            );
        }
        else {
            echo 'Ctrl-C to stop it.' . PHP_EOL;
            $command = sprintf(
                'php -S %s:%d -t %s %s >/dev/null',
                'localhost',
                '8000',
                $this->_path,
                sprintf('%s/%s/router.php', $this->_path, Api::PHPOOLE_DIRNAME)
            );
        }
        exec($command);
    }
}