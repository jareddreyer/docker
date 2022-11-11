<?php

declare(strict_types=1);
/*
 * Copyright 2021 Luis Alberto Pabón Flores
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 *
 */

namespace App\PHPDocker\Generator;

use App\PHPDocker\Generator\Files\DockerCompose;
use App\PHPDocker\Generator\Files\Dockerfile;
use App\PHPDocker\Generator\Files\EnvSettings;
use App\PHPDocker\Generator\Files\GitExclude;
use App\PHPDocker\Generator\Files\MailConf;
use App\PHPDocker\Generator\Files\NginxConf;
use App\PHPDocker\Generator\Files\PhpIni;
use App\PHPDocker\Generator\Files\ReadmeHtml;
use App\PHPDocker\Generator\Files\ReadmeMd;
use App\PHPDocker\Generator\Files\SSLConf;
use App\PHPDocker\Generator\Files\SelfSignedConf;
use App\PHPDocker\Generator\Files\WebConf;
use App\PHPDocker\Interfaces\ArchiveInterface;
use App\PHPDocker\Project\Project;
use App\PHPDocker\Zip\Archiver;
use Michelf\MarkdownExtra;
use Symfony\Component\Yaml\Dumper;
use Twig\Environment;

/**
 * Docker environment generator based on a Project.
 */
class Generator
{

    public function __construct(
        private Archiver $archiver,
        private Environment $twig,
        private MarkdownExtra $markdownExtra,
        private Dumper $yaml,
    ) {
    }

    /**
     * Generates all the files from the Project, and returns as an archive file.
     */
    public function generate(Project $project): ArchiveInterface
    {
        $readmeMd = new ReadmeMd($this->twig, $project);
        $phpIni   = new PhpIni($this->twig);

        $this->archiver
            ->addFile($readmeMd)
            ->addFile(new ReadmeHtml($this->twig, $this->markdownExtra, $readmeMd->getContents()))
            ->addFile(new Dockerfile($this->twig, $project))
            ->addFile(new NginxConf($this->twig, $project))
            ->addFile(new SSLConf($this->twig, $project))
            ->addFile(new SelfSignedConf($this->twig, $project))
            ->addFile(new WebConf($this->twig, $project))
            ->addFile(new EnvSettings($this->twig, $project))
            ->addFile(new MailConf($this->twig, $project))
            ->addFile(new GitExclude($this->twig, $project))
            ->addFile(new DockerCompose($this->yaml, $project, $phpIni->getFilename()), true);

        $this->archiver->setBaseFolder($this->getZipFolder($project));

        return $this->archiver->generateArchive($this->getArchiveName($project));
    }

    public function getZipFolder(Project $project): string
    {
        return $project->getGlobalOptions()->getProjectName();
    }

    public function getArchiveName(Project $project): string
    {
        return sprintf('%s.zip', $project->getGlobalOptions()->getProjectName());
    }
}
