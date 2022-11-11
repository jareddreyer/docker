<?php
declare(strict_types=1);
/**
 * Copyright 2016 Luis Alberto Pabón Flores
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
 */

namespace App\Form\Generator;

use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * Base form for MySQL-like options.
 */
abstract class AbstractMySQLType extends AbstractGeneratorType
{
    /**
     * Return the name of the field for 'hasWhatever'.
     */
    abstract protected function getHasOptionFieldName(): string;

    /**
     * Return the label of the field 'hasWhatever'.
     */
    abstract protected function getHasOptionLabel(): string;

    /**
     * Return the list of available versions for the version selector field.
     *
     * @return array<string, string>
     */
    abstract protected function getVersionChoices(): array;

    /**
     * Return the method name (bool) on the entity to work out whether option is enabled.
     */
    abstract protected function getHasOptionFunctionName(): string;

    /**
     * Return the name of the validation group for this form type.
     */
    abstract protected function getValidationGroup(): string;

    /**
     * Builds the form definition.
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $defaultConstraints = [
            new NotBlank(groups: [$this->getValidationGroup()]),
            new Length(min: 1, max: 128),
        ];

        $builder
            ->add($this->getHasOptionFieldName(), CheckboxType::class, [
                'label'    => $this->getHasOptionLabel(),
                'required' => false,
            ])
            ->add('version', ChoiceType::class, [
                'choices'     => $this->getVersionChoices(),
                'expanded'    => false,
                'multiple'    => false,
                'label'       => 'Version',
                'constraints' => $defaultConstraints,
            ])
            ->add('databaseName', TextType::class, [
                'label'       => 'DB name',
                'attr'        => ['placeholder' => 'Your app\'s database name'],
                'data'        => 'SS_mysite',
                'constraints' => $defaultConstraints,
            ])
            ->add('rootPassword', TextType::class, [
                'label'       => 'Password',
                'attr'        => ['placeholder' => 'Password for root user'],
                'data'        => 'root',
                'constraints' => $defaultConstraints,
            ]);
    }

    protected function getValidationGroups(): callable
    {
        return function (FormInterface $form) {
            /** @var array<mixed> $data */
            $data   = $form->getData();
            $groups = ['Default'];

            if ($data[$this->getHasOptionFunctionName()] === true) {
                $groups[] = $this->getValidationGroup();
            }

            return $groups;
        };
    }
}
