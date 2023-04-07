<?php

namespace DigraphCMS_Plugins\unmous\regalia\Forms;

use DigraphCMS\HTML\Forms\Field;
use DigraphCMS\HTML\Forms\FIELDSET;
use DigraphCMS\HTML\Forms\SELECT;

class RegaliaSizeField extends FIELDSET
{
    /** @var Field */
    protected $height;
    /** @var Field */
    protected $weight;
    /** @var Field */
    protected $gender;
    /** @var Field */
    protected $hat;

    public function __construct(string $label = 'Regalia size')
    {
        parent::__construct($label);
        $this->height = new Field('Height', new SELECT(static::heightOptions(58, 80)));
        $this->height->addClass('regalia-form__height');
        $this->addChild($this->height);
        $this->weight = new Field('Weight (pounds)');
        $this->weight->addClass('regalia-form__weight');
        $this->weight->input()->setAttribute('type', 'number'); // @phpstan-ignore-line
        $this->addChild($this->weight);
        $this->gender = new Field('Gender', new SELECT([
            0 => '-- select --',
            'M' => 'Male',
            'F' => 'Female',
            'O' => 'Other/prefer not to answer'
        ]));
        $this->gender->addClass('regalia-form__gender');
        $this->addChild($this->gender);
        $this->hat = new Field('Hat size', new SELECT([
            0 => '-- select --',
            'XS' => 'XS: 19-1/4" - 20-1/8"',
            'S' => 'S: 20-1/4" - 21-1/8"',
            'M' => 'M: 21-1/4" - 22-7/8"',
            'L' => 'L: 23" - 24-1/8"',
            'XL' => 'XL: 24-1/4" - 26"',
        ]));
        $this->hat->addClass('regalia-form__hat');
        $this->hat->addTip('Measure your head circumference 1" above your ears, and select hat size accordingly.');
        $this->addChild($this->hat);
    }

    public function height(): Field
    {
        return $this->height;
    }

    public function weight(): Field
    {
        return $this->weight;
    }

    public function gender(): Field
    {
        return $this->gender;
    }

    public function hat(): Field
    {
        return $this->hat;
    }

    public function setDefault(array $value)
    {
        $this->height->setDefault($value['height']);
        $this->weight->setDefault($value['weight'] ? abs(intval($value['weight'])) : null);
        $this->gender->setDefault($value['gender']);
        $this->hat->setDefault($value['hat']);
        return $this;
    }

    public function value()
    {
        return [
            'height' => $this->height->value() ? $this->height->value() : null,
            'weight' => $this->weight->value() ? abs($this->weight->value()) : null,
            'gender' => $this->gender->value() ? $this->gender->value() : null,
            'hat' => $this->hat->value() ? $this->hat->value() : null
        ];
    }

    protected static function heightOptions($start, $end): array
    {
        $options = [
            0 => '-- select --'
        ];
        for ($i = $start; $i <= $end; $i++) {
            $ft = floor($i / 12);
            $in = $i - ($ft * 12);
            $label = sprintf('%s\' %s"', $ft, $in);
            $label .= sprintf(' (%scm)', round($i * 2.54));
            if ($i == $start) $label .= ' or less';
            if ($i == $end) $label .= ' or more';
            $options[$i] = $label;
        }
        return $options;
    }
}
