<?php

namespace DigraphCMS_Plugins\unmous\regalia\Forms;

use DigraphCMS\HTML\Forms\Field;
use DigraphCMS\HTML\Forms\Fields\CheckboxField;
use DigraphCMS\HTML\Forms\FIELDSET;
use DigraphCMS\HTML\Forms\FormWrapper;

class RegaliaAlmaMaterField extends FIELDSET
{
    /** @var Field */
    protected $institution;
    /** @var CheckboxField */
    protected $notFound;
    /** @var Field */
    protected $notFound_name, $notFound_colors;

    public function __construct(string $label = 'Alma mater')
    {
        parent::__construct($label);
        $this->institution = new Field('Institution', new InstitutionInput());
        $this->addChild($this->institution);
        $this->notFound = new CheckboxField('I cannot locate the school where I got my degree');
        $this->notFound->addTip('Not all institutions are listed in the Jostens system from which our rental regalia is ordered.');
        $this->notFound->addTip('If you cannot find your alma mater in the above search box, check here and we will attempt to add it to our database using the most appropriate regalia available from Jostens. Someone may contact you directly for more information if necessary.');
        $this->addChild($this->notFound);
        $this->addClass('regalia-almamater-field');
        $this->institution->addClass('regalia-almamater-field__institution');
        $this->notFound->addClass('regalia-almamater-field__not-found');
        // extra fields for describing not found alma mater
        $this->notFound_name = (new Field('Alma mater name'))
            ->addTip('Enter the name of the school where you attained your highest degree, including the city if it is part of a network with multiple locations.')
            ->addClass('regalia-almamater-field__not-found-name');
        $this->notFound_colors = (new Field('Alma mater regalia colors'))
            ->addTip('We need to select two accent colors that will represent your alma mater on your hood. Please describe two colors that would be appropriate to represent your school on your hood.')
            ->addTip('An exact match may not be available, but we\'ll do our best to find an appropriate color combination that exists in the Jostens database.')
            ->addClass('regalia-almamater-field__not-found-colors');
        $this->addChild($this->notFound_name);
        $this->addChild($this->notFound_colors);
    }

    /**
     * Add pre-validation check to ensure name and colors fields are filled out
     * if notfound is checked.
     * 
     * @param FormWrapper $form 
     * @return $this 
     */
    public function setForm(FormWrapper $form)
    {
        parent::setForm($form);
        $form->addPreValidationCallback(function(){
            if ($this->notFound->value()) {
                $this->notFound_name->setRequired(true);
                $this->notFound_colors->setRequired(true);
            }
        });
        return $this;
    }

    public function institution(): Field
    {
        return $this->institution;
    }

    public function notFound(): CheckboxField
    {
        return $this->notFound;
    }

    public function notFoundValue(): array
    {
        return [
            'name' => $this->notFound_name->value(),
            'colors' => $this->notFound_colors->value(),
        ];
    }

    public function setNotFoundDefault(array|null $default): static
    {
        if ($default) {
            $this->notFound_name->setDefault($default['name']);
            $this->notFound_colors->setDefault($default['colors']);
        } else {
            $this->notFound_name->setDefault(null);
            $this->notFound_colors->setDefault(null);
        }
        return $this;
    }

    public function value(bool $useDefault = false)
    {
        if ($this->notFound->value($useDefault)) return false;
        return $this->institution->value($useDefault);
    }

    public function default()
    {
        if (!$this->notFound->default()) return false;
        return $this->institution->default();
    }

    public function setDefault($default)
    {
        if ($default === false) {
            $this->notFound->setDefault(true);
            $this->institution->setDefault(null);
        } elseif ($default === null) {
            $this->notFound->setDefault(false);
            $this->institution->setDefault(null);
        } else {
            $this->notFound->setDefault(false);
            $this->institution->setDefault($default);
        }
    }
}
