<?php

namespace DigraphCMS_Plugins\unmous\regalia\Forms;

use DigraphCMS\Context;
use DigraphCMS\HTML\DIV;
use DigraphCMS\HTML\Forms\Fields\CheckboxListField;
use DigraphCMS\HTML\Forms\FormWrapper;
use DigraphCMS\HTML\Forms\InputInterface;
use DigraphCMS\HTML\Tag;
use DigraphCMS\HTTP\RedirectException;
use DigraphCMS\URL\URL;
use DigraphCMS_Plugins\unmous\ous_digraph_module\PersonInfo;
use DigraphCMS_Plugins\unmous\regalia\Regalia;

class RegaliaInformationForm extends DIV
{
    protected $for;
    protected $form;

    public function __construct(string $for)
    {
        $this->for = $for;
        $this->addClass('navigation-frame navigation-frame--stateless');
        $this->setID('regalia-information-form--' . crc32($for));
    }

    public function setForm(FormWrapper $form)
    {
        $this->form = $form;
    }

    protected function currentChildObject(): Tag
    {
        if (Context::arg($this->id()) == 'edit') {
            return $this->buildForm();
        } else {
            return $this->buildDisplay();
        }
    }

    protected function buildDisplay(): Tag
    {
        $editURL = new URL('&' . $this->id() . '=edit');
        $div = new DIV;
        if ($info = Regalia::getPersonInfo($this->for)) {
            // display information
            $preset = Regalia::preset($info['preset_id']);
            $field = Regalia::field($info['field_id']);
            $institution = $info['institution_id'] ? Regalia::institution($info['institution_id']) : null;
            $div->addChild(sprintf(
                '<div><strong>Needed regalia pieces:</strong> %s</div>',
                implode(', ', array_filter([
                    $info['needs_hat'] ? 'hat' : false,
                    $info['needs_robe'] ? 'robe' : false,
                    $info['needs_hood'] ? 'hood' : false,
                ]))
            ));
            if (($info['needs_robe'] || $info['needs_hood']) && $preset) $div->addChild(sprintf(
                '<div><strong>Degree: </strong> %s%s</div>',
                @$preset['label'],
                !@$preset['field'] ? ' (' . @$field['label'] . ')' : ''
            ));
            if (($info['needs_robe'] || $info['needs_hood']) && $institution) $div->addChild(sprintf(
                '<div><strong>Alma mater: </strong> %s</div>',
                $institution['label']
            ));
            else if ($info['institution_notfound']) {
                $div->addChild('<div><strong>Alma mater: </strong> Marked as "not found." Someone may contact you for more information.</div>');
            }
            if ($info['needs_robe'] && $info['size_height']) $div->addChild(sprintf(
                '<div><strong>Robe size: </strong> %s\' %s", %slbs</div>',
                floor($info['size_height'] / 12),
                $info['size_height'] % 12,
                abs(intval($info['size_weight'])),
            ));
            if ($info['needs_hat'] && $info['size_hat']) $div->addChild(sprintf(
                '<div><strong>Hat size: </strong> %s</div>',
                $info['size_hat']
            ));
            if (!Regalia::validatePersonInfo($this->for, true)) {
                $div->addChild('<div class="notification notification--warning">Regalia information for <code>' . $this->for . '</code> needs to be updated due to changes to the form or available options</div>');
                $div->addChild(sprintf('<a href="%s" class="button">Update regalia information</a>', $editURL));
            } else {
                $div->addChild(sprintf('<p><small><a href="%s">Edit regalia information</a></small></p>', $editURL));
            }
            return $div;
        } else {
            $div->addChild('<p>No regalia information on file. Please complete this section to enter your regalia sizing and degree information. Your information will be automatically saved for next time you need to rent regalia.</p>');
            $div->addChild(sprintf('<a href="%s" class="button">Enter regalia information</a>', $editURL));
            return $div;
        }
    }

    protected function buildForm(): FormWrapper
    {
        $form = new FormWrapper($this->id() . '__form');
        $form->addClass('regalia-form');

        $parts = new CheckboxListField('Which pieces of regalia do you need to rent?', [
            'hat' => 'Hat',
            'robe' => 'Robe',
            'hood' => 'Hood'
        ]);
        $parts->addClass('regalia-form__parts');
        $parts->setRequired(true);
        $form->addChild($parts);

        // Set up input fields

        $degree = new RegaliaDegreeField();
        $degree->addClass('regalia-form__degree');
        $form->addChild($degree);

        $almaMater = new RegaliaAlmaMaterField();
        $almaMater->addClass('regalia-form__alma-mater');
        $form->addChild($almaMater);

        $size = new RegaliaSizeField();
        $size->addClass('regalia-form__size');
        $form->addChild($size);

        // Set up validators to ensure required info is there, based on selected parts

        $hatValidator = function (InputInterface $input) use ($parts) {
            if (!in_array('hat', $parts->value() ?? [])) return null;
            elseif (!$input->value()) return "This field is required if you are ordering a hat";
            else return null;
        };

        $robeValidator = function (InputInterface $input) use ($parts) {
            if (!in_array('robe', $parts->value() ?? [])) return null;
            elseif (!$input->value()) return "This field is required if you are ordering a robe";
            else return null;
        };

        $hoodValidator = function (InputInterface $input) use ($parts) {
            if (!in_array('hood', $parts->value() ?? [])) return null;
            elseif (!$input->value()) return "This field is required if you are ordering a hood";
            else return null;
        };

        $size->hat()->addValidator($hatValidator);

        $size->height()->addValidator($robeValidator);
        $size->weight()->addValidator($robeValidator);
        $degree->type()->addValidator($robeValidator);

        $degree->type()->addValidator($hoodValidator);

        // alma mater has two inputs, so it needs a special validator

        $almaMater->institution()->addValidator(function () use ($parts, $almaMater) {
            if (!in_array('hood', $parts->value() ?? [])) return null;
            if (!$almaMater->institution()->value() && !$almaMater->notFound()->value()) {
                return 'This field is required if you are ordering a hood or robe';
            } else return null;
        });

        // extra validator for degree type/level fields

        $degree->field()->addValidator(function () use ($parts, $degree) {
            if (!in_array('hood', $parts->value() ?? []) && !in_array('robe', $parts->value() ?? [])) return null;
            elseif (!$degree->type()->value()) return null;
            elseif (substr($degree->type()->value(), 0, 8) != '[preset]') {
                if (!$degree->field()->value()) return 'This field is required for this degree type/level';
            } else return null;
        });

        // Set defaults from logged person info

        if ($person = Regalia::getPersonInfo($this->for)) {
            $parts->setDefault(array_filter([
                $person['needs_hat'] ? 'hat' : false,
                $person['needs_robe'] ? 'robe' : false,
                $person['needs_hood'] ? 'hood' : false,
            ]));
            $degree->setDefault([
                'preset_id' => $person['preset_id'],
                'field_id' => $person['field_id']
            ]);
            $almaMater->setDefault($person['institution_id']);
            $almaMater->notFound()->setDefault($person['institution_notfound']);
            $size->setDefault([
                'height' => $person['size_height'],
                'weight' => $person['size_weight'],
                'hat' => $person['size_hat']
            ]);
        }

        // set not found data from PersonInfo

        $almaMater->setNotFoundDefault(PersonInfo::getFor($this->for, 'institution_notfound'));

        // set up callback to save everything

        $form->addCallback(function () use ($parts, $degree, $almaMater, $size) {
            $value = [
                'identifier' => $this->for,
                'preset_id' => $degree->value()['preset_id'],
                'field_id' => $degree->value()['field_id'],
                'institution_id' => $almaMater->value() ? $almaMater->value() : null,
                'institution_notfound' => $almaMater->notFound()->value() ? 1 : 0,
                'needs_hat' => in_array('hat', $parts->value()) ? 1 : 0,
                'needs_robe' => in_array('robe', $parts->value()) ? 1 : 0,
                'needs_hood' => in_array('hood', $parts->value()) ? 1 : 0,
                'size_height' => $size->value()['height'],
                'size_weight' => $size->value()['weight'],
                'size_hat' => $size->value()['hat']
            ];
            PersonInfo::setFor(
                $this->for,
                [
                    'institution_notfound' => $almaMater->notFoundValue()
                ]
            );
            if (Regalia::getPersonInfo($this->for)) {
                Regalia::query()
                    ->update('regalia_person', $value)
                    ->where('identifier = ?', [$this->for])
                    ->execute();
            } else {
                Regalia::query()
                    ->insertInto('regalia_person', $value)
                    ->execute();
            }
            throw new RedirectException(new URL('&' . $this->id() . '=view'));
        });
        $form->button()->setText('Save regalia information');
        return $form;
    }

    public function children(): array
    {
        return array_merge(
            [$this->currentChildObject()],
            parent::children()
        );
    }
}
