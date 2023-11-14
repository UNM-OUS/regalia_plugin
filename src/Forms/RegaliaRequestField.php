<?php

namespace DigraphCMS_Plugins\unmous\regalia\Forms;

use DigraphCMS\HTML\Forms\Fields\CheckboxField;
use DigraphCMS\HTML\Forms\FIELDSET;
use DigraphCMS\HTML\Forms\FormWrapper;
use DigraphCMS\UI\Notifications;
use DigraphCMS_Plugins\unmous\ous_digraph_module\OUS;
use DigraphCMS_Plugins\unmous\ous_digraph_module\PersonInfo;
use DigraphCMS_Plugins\unmous\regalia\Regalia;

class RegaliaRequestField extends FIELDSET
{
    protected $for, $infoForm, $needsRegalia;

    public function __construct(string $label, string $for)
    {
        parent::__construct($label);
        $this->for = $for;
        $this->needsRegalia = new CheckboxField('I need to rent regalia');
        $this->needsRegalia->addClass('regalia-request-field__needs-regalia');
        $this->addChild($this->needsRegalia);
        $this->infoForm = new RegaliaInformationForm($for);
        $this->infoForm->addClass('regalia-request-field__info-form');
        $this->addChild($this->infoForm);
        $this->addClass('regalia-request-field');
        // display notification if this form is not for the current user and
        // the person the form is for has previously opted out of regalia
        if (!str_contains($for, '@')) {
            $netIds = OUS::userNetIDs();
            if (!in_array($for, $netIds)) {
                if (PersonInfo::getFor($for, 'regalia') === false) {
                    Notifications::notice(sprintf(
                        "The person you are filling this form out for <kbd>%s</kbd> has previously opted out of regalia rental. " .
                            "Unless you know otherwise, it is likely that they own their own regalia and do not need a rental.",
                        $for
                    ));
                }
            }
        }
        // validator to require either opting out or an existing person record
        $this->needsRegalia->addValidator(function () {
            if (!$this->needsRegalia->value()) return null;
            elseif (!Regalia::getPersonInfo($this->for)) return "You must either opt out of regalia rental or enter the information necessary to pick the regalia that you need";
            else return null;
        });
        // validator to require either opting out or an existing person record
        $this->needsRegalia->addValidator(function () {
            if (!$this->needsRegalia->value()) return null;
            elseif (!Regalia::validatePersonInfo($this->for, true)) return "You must update your regalia information to submit this form";
            else return null;
        });
    }

    public function checkbox(): CheckboxField
    {
        return $this->needsRegalia;
    }

    /**
     * @param string $tip
     * @return static
     */
    public function addTip(string $tip)
    {
        $this->needsRegalia->addTip($tip);
        return $this;
    }

    /**
     * Helper to add field to a form without breaking fluent chaining.
     *
     * @param FormWrapper $form
     * @return static
     */
    public function addForm(FormWrapper $form)
    {
        $form->addChild($this);
        return $this;
    }

    public function value(bool $useDefault = false)
    {
        return $this->needsRegalia->value($useDefault);
    }

    public function default(): bool
    {
        return $this->needsRegalia->default();
    }

    /**
     * Set default of whether regalia is requested
     *
     * @param boolean $default
     * @return static
     */
    public function setDefault(bool $default)
    {
        $this->needsRegalia->setDefault($default);
        return $this;
    }
}
