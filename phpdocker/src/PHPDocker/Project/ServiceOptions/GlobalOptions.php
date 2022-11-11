<?php
declare(strict_types=1);

namespace App\PHPDocker\Project\ServiceOptions;

final class GlobalOptions extends Base
{

    public function __construct(
        private int $basePort,
        private string $appPath,
        private string $dockerWorkingDir,
        private string $projectName,
        private bool $appleM1Chip,
        private int $nfsVersion
    ){
    }

    public function getBasePort(): int
    {
        return $this->basePort;
    }

    public function getAppPath(): ?string
    {
        return $this->appPath;
    }

    public function getDockerWorkingDir(): ?string
    {
        return $this->dockerWorkingDir;
    }

    public function getProjectName(): ?string
    {
        return $this->projectName;
    }

    public function getAppleM1Chip(): bool
    {
        return $this->appleM1Chip;
    }

    public function getNFSVersion(): int
    {
        return $this->nfsVersion;
    }
}
