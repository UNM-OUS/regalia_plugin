<?php

namespace DigraphCMS_Plugins\unmous\regalia\Forms;

use DigraphCMS\HTML\Forms\Field;
use DigraphCMS\HTML\Forms\FIELDSET;
use DigraphCMS_Plugins\unmous\regalia\Regalia;

class RegaliaDegreeField extends FIELDSET
{
    /** @var Field */
    protected $type;
    /** @var Field */
    protected $field;

    public function __construct(string $label = 'Degree')
    {
        parent::__construct($label);
        $this->type = new Field('Degree type/level', new DegreeSelect());
        $this->addChild($this->type);
        $this->field = new Field('Degree field/discipline', new DegreeFieldInput());
        $this->field->addTip('The degree fields available here include many broad categories provided by Jostens for coloring regalia, but few specific disciplines.');
        $this->field->addTip('If you cannot locate your exact major, please attempt to find a broader category under which your specific discipline logically belongs.');
        $this->addChild($this->field);
        $this->addClass('regalia-degree-field');
        $this->type->addClass('regalia-degree-field__type');
        $this->field->addClass('regalia-degree-field__field');
    }

    public function type(): Field
    {
        return $this->type;
    }

    public function field(): Field
    {
        return $this->field;
    }

    public function setDefault(array $value = null)
    {
        if ($value) {
            $preset = Regalia::preset($value['preset_id']);
            if ($preset['field_id']) {
                $this->type->setDefault('[preset]' . $preset['id']);
            } else {
                $this->type->setDefault($preset['id']);
                $this->field->setDefault($value['field_id']);
            }
        }
        return $this;
    }

    public function value(bool $useDefault = false)
    {
        if (!$this->type->value()) return null;
        $presetID = preg_replace('/^\[preset\]/', '', $this->type->value($useDefault));
        $preset = Regalia::preset($presetID);
        if (!$preset) return null;
        $fieldID = $preset['field_id'] ?? $this->field->value();
        if (!$fieldID) return null;
        return [
            'preset_id' => $presetID ? $presetID : null,
            'field_id' => $fieldID ? $fieldID : null //@phpstan-ignore-line
        ];
    }
}
