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

namespace App\PHPDocker\Generator\Files;

use App\PHPDocker\Interfaces\GeneratedFileInterface;
use App\PHPDocker\Project\Project;
use Symfony\Component\Yaml\Dumper;

class DockerCompose implements GeneratedFileInterface
{
    private const DOCKER_COMPOSE_FILE_VERSION = '3.1';

    /** @var array<string, mixed> */
    private array  $services;
    private array  $networks;
    private array $volumes;
    private string $defaultVolume;
    private bool $isM1Chip;
    private string $projectName;
    private int    $basePort;

    public function __construct(private Dumper $yaml, private Project $project, private string $phpIniLocation)
    {
        $this->basePort = $this->project->getGlobalOptions()->getBasePort();
        $this->projectName =  strtolower($this->project->getGlobalOptions()->getProjectName());
        $this->isM1Chip = $this->project->getGlobalOptions()->getAppleM1Chip();
    }

    public function getContents(): string
    {

        // Add Volumes
        $this->addVolumes();

        // Add Network
        $this->addNetwork();

        // Build the Services
        $this
            ->addMailhog()
            ->addMysql()
            ->addElasticsearch()
            ->addUbuntuServer();

        $data = [
            'version'  => self::DOCKER_COMPOSE_FILE_VERSION,
            'services' => $this->services,
            'networks' => $this->networks,
            'volumes' => $this->volumes,
        ];

        return $this->tidyYaml($this->yaml->dump(input: $data, inline: 4));
    }

    public function getFilename(): string
    {
        return 'docker-compose.yml';
    }

    private function addNetwork(): self
    {
        $this->networks[$this->projectName] = [
            'driver' => 'bridge'
        ];

        return $this;
    }

    private function addVolumes(): self
    {
        if ($this->project->hasMysql() === true) {
            $this->volumes[sprintf('%s-mysql-data', $this->projectName)] = [
                'driver' => 'local'
            ];
        }

        if ($this->project->hasElasticsearch() === true) {
            $this->volumes[sprintf('%s-elasticsearch-data', $this->projectName)] = [
                'driver' => 'local'
            ];

            $this->volumes[sprintf('%s-enterprisesearch-data', $this->projectName)] = [
                'driver' => 'local'
            ];
        }

        $workingDir = $this->project->getGlobalOptions();

        if ($this->project->getGlobalOptions()->getNFSVersion() > 0) {
            $this->defaultVolume = sprintf('%s-www-nfsmount:%s', $this->projectName, $workingDir->getDockerWorkingDir());
            $this->volumes[sprintf('%s-www-nfsmount', $this->projectName)] = [
                'driver' => 'local',
                'driver_opts' => [
                    'type' => 'nfs',
                    'o' => sprintf('addr=host.docker.internal,rw,nolock,hard,nointr,nfsvers=%s', $this->project->getGlobalOptions()->getNFSVersion()),
                    'device' => ':${PWD}'
                ]
            ];
        }else {
            $this->defaultVolume = sprintf('%s:%s', $workingDir->getAppPath(), $workingDir->getDockerWorkingDir());
            $this->volumes[sprintf('%s-www-data', $this->projectName)] = [
                'driver' => 'local'
            ];
        }

        return $this;
    }

    private function addMailhog(): self
    {
        if ($this->project->hasMailhog() === true) {
            $serviceName = sprintf('%s-mailhog', $this->projectName);
            $extPort = $this->project->getMailhogOptions()->getExternalPort($this->basePort);

            $this->services[$serviceName] = [
                'image' => 'mailhog/mailhog:latest',
                'container_name' => $serviceName,
                'ports' => [
                    '1025:1025',
                    '${DEV_MAILER_PORT}:8025',
                ],
                'networks'  => [
                    $this->projectName
                ]
            ];

            if ($this->isM1Chip) {
                $this->services[$serviceName] += ['platform' => 'linux/x86_64'];
            }
        }

        return $this;
    }

    private function addMysql(): self
    {
        if ($this->project->hasMysql() === true) {
            $serviceName = sprintf('%s-mysql', $this->projectName);
            $mysql   = $this->project->getMysqlOptions();
            $extPort = $mysql->getExternalPort($this->basePort);

            $this->services[$serviceName] = [
                'image'       => sprintf('mysql:%s', $mysql->getVersion()),
                'container_name' => $serviceName,
                'working_dir' => $this->project->getGlobalOptions()->getDockerWorkingDir(),
                'volumes'     => [$this->defaultVolume],
                'environment' => [
                    'MYSQL_ROOT_PASSWORD=${SS_DATABASE_ROOT_PASSWORD}',
                    'MYSQL_DATABASE=${SS_DATABASE_NAME}',
                    'TZ=${TZ}',
                ],
                'ports'       => [
                    '${SS_DATABASE_LOCAL_PORT}:3306'
                ],
                'networks'  => [
                    $this->projectName
                ]
            ];

            if ($this->isM1Chip) {
                $this->services[$serviceName] += ['platform' => 'linux/x86_64'];
            }
        }

        return $this;
    }

    private function addElasticsearch(): self
    {
        if ($this->project->hasElasticsearch() === true) {
            $serviceName = sprintf('%s-elasticsearch', $this->projectName);
            $volumneName = sprintf('%s-elasticsearch-data', $this->projectName);
            $this->services[$serviceName] = [
                'image' => sprintf('docker.elastic.co/elasticsearch/elasticsearch:%s', $this->project->getElasticsearchOptions()->getVersion()),
                'container_name' => $serviceName,
                'networks'  => [
                    $this->projectName
                ],
                'volumes' => [
                    sprintf('%s-elasticsearch-data: /usr/share/elasticsearch/data', $this->projectName)
                ],
                'ports' => [
                    '9200:9200',
                    '9300:9300',
                ],
                'environment' => [
                    'ES_JAVA_OPTS=-Xms1g -Xmx1g',
                    'discovery.type=single-node',
                    'node.name=es101',
                    'cluster.name=es-docker-cluster',
                ],
                'depends_on' => [
                    sprintf('%s-www', $this->projectName)
                ]
            ];

            if ($this->isM1Chip) {
                $this->services[$serviceName] += ['platform' => 'linux/x86_64'];
            }

            $serviceName = sprintf('%s-enterprisesearch', $this->projectName);
            $this->services[$serviceName] = [
                'image' => sprintf('docker.elastic.co/enterprise-search/enterprise-search:%s', $this->project->getElasticsearchOptions()->getVersion()),
                'container_name' => $serviceName,
                'networks'  => [
                    $this->projectName
                ],
                'volumes' => [
                    sprintf('%s-enterprisesearch-data: /usr/share/enterprisesearch/data', $this->projectName)
                ],
                'ports' => [
                    '3002:3002'
                ],
                'environment' => [
                    'allow_es_settings_modification=true',
                    sprintf("elasticsearch.host='http://%s-elasticsearch:9200'", $this->projectName),
                    'elasticsearch.username=elastic',
                    'elasticsearch.password=changeme',
                    'secret_management.encryption_keys=[4a2cd3f81d39bf28738c10db0ca782095ffac07279561809eecc722e0c20eb09]',
                ],
                'depends_on' => [
                    sprintf('%s-elasticsearch', $this->projectName)
                ]
            ];

            if ($this->isM1Chip) {
                $this->services[$serviceName] += ['platform' => 'linux/x86_64'];
            }
        }

        return $this;
    }

    private function addUbuntuServer(): self
    {
        $shortVersion = str_replace(search: '.x', replace: '', subject: $this->project->getPhpOptions()->getVersion());
        $serviceName = sprintf('%s-www', $this->projectName);

        $this->services[$serviceName] = [
            'build'       => '.docker',
            'container_name' => $serviceName,
            'working_dir' => $this->project->getGlobalOptions()->getDockerWorkingDir(),
            'volumes'     => [
                $this->defaultVolume,
            ],
            'networks'  => [
                $this->projectName
            ],
            'ports' => [
                '${WWW_HTTP_PORT}:80',
                '${WWW_HTTPS_PORT}:443',
                '8983:8983',
            ]
        ];

        if ($this->isM1Chip) {
            $this->services[$serviceName] += ['platform' => 'linux/x86_64'];
        }

        return $this;
    }

    private function addWebserver(): self
    {
        $serviceName = sprintf('%s-webserver', $this->projectName);
        $this->services[$serviceName] = [
            'image'       => 'nginx:alpine',
            'container_name' => $serviceName,
            'working_dir' => $this->project->getGlobalOptions()->getDockerWorkingDir(),
            'volumes'     => [
                $this->defaultVolume,
                './phpdocker/nginx/nginx.conf:/etc/nginx/conf.d/default.conf',
            ],
            'ports'       => [sprintf('%s:80', $this->basePort)],
            'networks'  => [
                $this->projectName
            ]
        ];

        if ($this->isM1Chip) {
            $this->services[$serviceName] += ['platform' => 'linux/x86_64'];
        }

        return $this;
    }

    private function addPhpFpm(): self
    {
        $shortVersion = str_replace(search: '.x', replace: '', subject: $this->project->getPhpOptions()->getVersion());
        $serviceName = sprintf('%s-php-fpm', $this->projectName);

        $this->services[$serviceName] = [
            'build'       => 'phpdocker/php-fpm',
            'container_name' => $serviceName,
            'working_dir' => $this->project->getGlobalOptions()->getDockerWorkingDir(),
            'volumes'     => [
                $this->defaultVolume,
                sprintf('./phpdocker/%s:/etc/php/%s/fpm/conf.d/99-overrides.ini', $this->phpIniLocation, $shortVersion),
            ],
            'networks'  => [
                $this->projectName
            ]
        ];

        if ($this->isM1Chip) {
            $this->services[$serviceName] += ['platform' => 'linux/x86_64'];
        }

        return $this;
    }

    private function tidyYaml(string $renderedYaml): string
    {
        return $this->addEmptyLinesBetweenItems($this->prependHeader($renderedYaml));
    }

    private function prependHeader(string $renderedYaml): string
    {
        $header = <<<TEXT
###############################################################################
#                          Generated on ss-php-docker.io                      #
###############################################################################

TEXT;

        return $header . $renderedYaml;
    }

    /**
     * Format YAML string to add empty lines between block objects.
     *
     * @see https://github.com/symfony/symfony/issues/22421
     */
    private function addEmptyLinesBetweenItems(string $result): string
    {
        $i = 0;

        $matcher = static function ($match) use (&$i) {
            ++$i;
            if ($i === 1) {
                return $match[0];
            }

            return PHP_EOL . $match[0];
        };

        return preg_replace_callback('#^[\s]{4}[a-zA-Z_]+#m', $matcher, $result) ?? $result;
    }
}
