<?php

namespace DigraphCMS_Plugins\unmous\regalia;

use DateTime;
use DigraphCMS\DB\DB;
use DigraphCMS\Events\Dispatcher;
use DigraphCMS\URL\URL;
use DigraphCMS_Plugins\unmous\ous_digraph_module\PersonInfo;
use DigraphCMS_Plugins\unmous\ous_digraph_module\Semester;
use DigraphCMS_Plugins\unmous\ous_digraph_module\Semesters;
use Flatrr\FlatArray;

class RegaliaOrder
{
    protected $id, $group_id, $type;
    protected $semester, $group_type, $group_tams; // joined from regalia_group
    protected $identifier, $email, $last_name, $first_name;
    protected $size_height, $size_weight, $size_hat;
    protected $degree_level, $degree_field;
    protected $inst_name, $inst_city, $inst_state;
    protected $color_band, $color_lining, $color_chevron;
    protected $hat, $tam, $hood, $robe, $cancelled;
    /** @var string|null|FlatArray */
    protected $data;
    protected $created;

    protected function __construct()
    {
        // remove unnecessary data
        if (!$this->hat) $this->size_hat = null;
        if (!$this->robe) {
            $this->size_height = null;
            $this->size_weight = null;
        }
        if (!$this->hood) {
            $this->degree_field = null;
            $this->inst_name = null;
            $this->inst_city = null;
            $this->inst_state = null;
            $this->color_band = null;
            $this->color_lining = null;
            $this->color_chevron = null;
        }
        if (!$this->robe && !$this->hood) {
            $this->degree_level = null;
            $this->degree_field = null;
        }
    }

    public function orderName(): string
    {
        return sprintf(
            '%s #%s',
            $this->type(),
            substr(
                str_pad(
                    strval($this->id()),
                    3,
                    '0',
                    STR_PAD_LEFT
                ),
                -3
            )
        );
    }

    /**
     * Delete this order if the group is not locked. Also unset its assignment
     * to any requests.
     *
     * @return boolean
     */
    public function delete(): bool
    {
        // runs in a transaction
        DB::beginTransaction();
        // dispatch events to clean up anything external
        Dispatcher::dispatchEvent('onDeleteRegaliaOrder', [$this]);
        // unset any regalia request assignments
        DB::query()
            ->update(
                'regalia_request',
                ['assigned_order' => null]
            )
            ->where('assigned_order', $this->id())
            ->execute();
        // delete any associated billing
        DB::query()
            ->delete('regalia_billing')
            ->where('order_id', $this->id())
            ->execute();
        // do delete -- only actually deletes if group isn't locked
        // this is to preserve useful historical regalia order data
        $out = !!DB::query()
            ->delete('regalia_order', $this->id())->execute();
        // commit and output result
        DB::commit();
        return $out;
    }

    /**
     * Cancel this order if the group is not locked.
     *
     * @return bool
     */
    public function cancel(): bool
    {
        // skip if group is locked
        if ($this->group()->ordersLocked() || $this->group()->cancellationLocked()) return false;
        // otherwise cancel
        return $this->setCancelled(true)
            ->save();
    }

    public function save(): bool
    {
        $update = [];
        // call hooks
        Dispatcher::dispatchEvent('onRegaliaOrderUpdate', [$this]);
        // ensure valid data
        static::assertObjectDataIsValid();
        // always update data, identifier, name, and email
        $update['data'] = json_encode($this->data()->get());
        $update['type'] = $this->type();
        $update['identifier'] = $this->identifier();
        $update['last_name'] = $this->lastName();
        $update['first_name'] = $this->firstName();
        $update['email'] = $this->email();
        $update['group_id'] = $this->group_id;
        // only update sizes and such if unlocked
        if (!$this->group()->ordersLocked()) {
            $update['size_height'] = $this->height();
            $update['size_weight'] = $this->weight();
            $update['size_hat'] = $this->hatSize();
            $update['tam'] = $this->tamPreference(); // raw value so we don't merge in collapsed setting from order group
            $update['degree_level'] = $this->degreeLevel();
            $update['degree_field'] = $this->degreeField();
            $update['inst_name'] = $this->institutionName();
            $update['inst_city'] = $this->institutionCity();
            $update['inst_state'] = $this->institutionState();
            $update['color_band'] = $this->colorBand();
            $update['color_lining'] = $this->colorLining();
            $update['color_chevron'] = $this->colorChevron();
            $update['hat'] = $this->hat();
            $update['hood'] = $this->hood();
            $update['robe'] = $this->robe();
        }
        // only update cancellation if unlocked
        if (!$this->group()->cancellationLocked()) {
            $update['cancelled'] = $this->cancelled();
        }
        // update and return
        $out = !!DB::query()->update(
            'regalia_order',
            $update,
            $this->id()
        )->execute();
        DB::commit();
        return $out;
    }

    public function url(): URL
    {
        return new URL('/regalia/order:' . $this->id());
    }

    /**
     * @param string $identifier
     * @return static
     */
    public function setIdentifier(string $identifier)
    {
        $this->identifier = $identifier;
        return $this;
    }

    /**
     * @param string $lastName
     * @return static
     */
    public function setLastName(string $lastName)
    {
        $this->last_name = $lastName;
        return $this;
    }

    /**
     * @param string $firstName
     * @return static
     */
    public function setFirstName(string $firstName)
    {
        $this->first_name = $firstName;
        return $this;
    }

    /**
     * @param string $email
     * @return static
     */
    public function setEmail(string $email)
    {
        $this->email = $email;
        return $this;
    }

    /**
     * @param string $type
     * @return static
     */
    public function setType(string $type)
    {
        $this->type = $type;
        return $this;
    }

    /**
     * @param boolean $cancelled
     * @return static
     */
    public function setCancelled(bool $cancelled)
    {
        $this->cancelled = $cancelled;
        return $this;
    }

    public function cancelled(): bool
    {
        return $this->cancelled;
    }

    public function order_jostens(): array
    {
        $order = [];
        if ($this->hood()) $order[] = 'HOOD';
        if ($this->hat() && $this->robe()) {
            $order[] = $this->tam()
                ? 'PACKAGE T/G'
                : 'PACKAGE C/G';
        } else {
            if ($this->hat()) $order[] = $this->tam() ? 'TAM' : 'CAP';
            if ($this->robe()) $order[] = 'GOWN';
        }
        return $order;
    }

    public function id(): int
    {
        return $this->id;
    }

    /**
     * @param RegaliaGroup $group
     * @return static
     */
    public function setGroup(RegaliaGroup $group)
    {
        if ($group->ordersLocked()) return $this;
        if ($this->group()->ordersLocked()) return $this;
        $this->group_id = $group->id();
        return $this;
    }

    public function group(): RegaliaGroup
    {
        return RegaliaGroups::get($this->group_id);
    }

    public function type(): string
    {
        return $this->type;
    }

    public function semester(): Semester
    {
        return Semesters::fromCode($this->semester);
    }

    public function identifier(): ?string
    {
        return $this->identifier;
    }

    public function email(): ?string
    {
        return $this->email;
    }

    public function lastName(): ?string
    {
        return $this->last_name;
    }

    public function firstName(): ?string
    {
        return $this->first_name;
    }

    /**
     * @param integer|null $inches
     * @return static
     */
    public function setHeight(?int $inches)
    {
        $this->size_height = $inches;
        return $this;
    }

    public function height(): ?int
    {
        return $this->size_height;
    }

    public function heightHR(): ?string
    {
        if (!$this->height()) return null;
        return sprintf(
            '%s\' %s"',
            floor($this->height() / 12),
            $this->height() % 12
        );
    }

    /**
     * @param integer $lbs
     * @return static
     */
    public function setWeight(?int $lbs)
    {
        $this->size_weight = $lbs;
        return $this;
    }

    public function weight(): ?int
    {
        return $this->size_weight;
    }

    /**
     * @param string|null $size
     * @return static
     */
    public function setHatSize(?string $size)
    {
        $this->size_hat = $size;
        return $this;
    }

    public function hatSize(): ?string
    {
        return $this->size_hat;
    }

    /**
     * @param string|null $level
     * @return static
     */
    public function setDegreeLevel(?string $level)
    {
        $this->degree_level = $level;
        return $this;
    }

    public function degreeLevel(): ?string
    {
        return $this->degree_level;
    }

    /**
     * @param string|null $field
     * @return static
     */
    public function setDegreeField(?string $field)
    {
        $this->degree_field = $field;
        return $this;
    }

    public function degreeField(): ?string
    {
        return $this->degree_field;
    }

    /**
     * @param string|null $name
     * @return static
     */
    public function setInstitutionName(?string $name)
    {
        $this->inst_name = $name;
        return $this;
    }

    public function institutionName(): ?string
    {
        return $this->inst_name;
    }

    /**
     * @param string|null $city
     * @return static
     */
    public function setInstitutionCity(?string $city)
    {
        $this->inst_city = $city;
        return $this;
    }

    public function institutionCity(): ?string
    {
        return $this->inst_city;
    }

    /**
     * @param string|null $state
     * @return static
     */
    public function setInstitutionState(?string $state)
    {
        $this->inst_state = $state;
        return $this;
    }

    public function institutionState(): ?string
    {
        return $this->inst_state;
    }

    /**
     * @param string|null $color
     * @return static
     */
    public function setColorLining(?string $color)
    {
        $this->color_lining = $color;
        return $this;
    }

    public function colorLining(): ?string
    {
        return $this->color_lining;
    }

    /**
     * @param string|null $color
     * @return static
     */
    public function setColorChevron(?string $color)
    {
        $this->color_chevron = $color;
        return $this;
    }

    public function colorChevron(): ?string
    {
        return $this->color_chevron;
    }

    /**
     * @param string|null $color
     * @return static
     */
    public function setColorBand(?string $color)
    {
        $this->color_band = $color;
        return $this;
    }

    public function colorBand(): ?string
    {
        return $this->color_band;
    }

    /**
     * @param boolean|null $tam
     * @return static
     */
    public function setTam(?bool $tam)
    {
        $this->tam = $tam;
        return $this;
    }

    public function tam(): bool
    {
        // doctoral degrees can get a tam in any circumstance
        if ($this->degreeLevel() == "DOCTOR") {
            return $this->tam ?? ($this->group_tams == 1);
        }
        // everyone else can only get a tam if they're in a platform group
        elseif ($this->group_type == 'platform') {
            return $this->tam ?? ($this->group_tams == 1);
        }
        // everyone else gets a mortarboard
        else {
            return false;
        }
    }

    public function tamPreference(): ?bool
    {
        return $this->tam;
    }

    /**
     * @param boolean $hat
     * @return static
     */
    public function setHat(bool $hat)
    {
        $this->hat = $hat;
        return $this;
    }

    public function hat(): bool
    {
        return $this->hat;
    }

    /**
     * @param boolean $hood
     * @return static
     */
    public function setHood(bool $hood)
    {
        $this->hood = $hood;
        return $this;
    }

    public function hood(): bool
    {
        return $this->hood;
    }

    /**
     * @param boolean $robe
     * @return static
     */
    public function setRobe(bool $robe)
    {
        $this->robe = $robe;
        return $this;
    }

    public function robe(): bool
    {
        return $this->robe;
    }

    public function data(): FlatArray
    {
        if (is_string($this->data)) $this->data = new FlatArray(json_decode($this->data, true, 512, JSON_THROW_ON_ERROR));
        return $this->data;
    }

    public function created(): DateTime
    {
        return (new DateTime())->setTimestamp($this->created);
    }

    public static function createExtra(
        RegaliaGroup $group,
        bool $hat,
        bool $hood,
        bool $robe,
        ?int $size_height,
        ?int $size_weight,
        ?string $size_hat
    ): RegaliaOrder {
        return static::createRaw(
            $group,
            'extra',
            null,
            null,
            null,
            null,
            $size_height,
            $size_weight,
            $size_hat,
            'DOCTOR',
            'PHD',
            'UNIVERSITY OF NEW MEXICO',
            'ALBUQUERQUE',
            'NM',
            'Royal Blue',
            'Red',
            'Silver Gray',
            $hat,
            null,
            $hood,
            $robe,
            []
        );
    }

    public static function createForPerson(
        RegaliaGroup $group,
        string $person_identifier,
        string $type,
        ?bool $tam,
        array $data = []
    ): ?RegaliaOrder {
        $person = Regalia::getFullPersonInfo($person_identifier);
        if (!$person) return null;
        return static::createRaw(
            $group,
            $type,
            $person_identifier,
            PersonInfo::getFirstNameFor($person_identifier),
            PersonInfo::getLastNameFor($person_identifier),
            PersonInfo::getFor($person_identifier, 'email'),
            $person['size_height'],
            $person['size_weight'],
            $person['size_hat'],
            $person['degree_level'],
            $person['degree_field'],
            $person['inst_name'],
            $person['inst_city'],
            $person['inst_state'],
            $person['color_band'],
            $person['color_lining'],
            $person['color_chevron'],
            $person['needs_hat'] == '1',
            $tam,
            $person['needs_hood'] == '1',
            $person['needs_robe'] == '1',
            $data
        );
    }

    public static function createUnmForPerson(
        RegaliaGroup $group,
        string $person_identifier,
        string $type,
        ?bool $tam,
        array $data = []
    ): ?RegaliaOrder {
        $person = Regalia::getFullPersonInfo($person_identifier);
        if (!$person) return null;
        return static::createRaw(
            $group,
            $type,
            $person_identifier,
            PersonInfo::getFirstNameFor($person_identifier),
            PersonInfo::getLastNameFor($person_identifier),
            PersonInfo::getFor($person_identifier, 'email'),
            $person['size_height'],
            $person['size_weight'],
            $person['size_hat'],
            $person['degree_level'],
            $person['degree_field'],
            'UNIVERSITY OF NEW MEXICO',
            'ALBUQUERQUE',
            'NM',
            $person['color_band'],
            'Red',
            'Silver Gray',
            $person['needs_hat'] == '1',
            $tam,
            $person['needs_hood'] == '1',
            $person['needs_robe'] == '1',
            $data
        );
    }

    public function assertObjectDataIsValid()
    {
        static::assertDataIsValid(
            $this->hat(),
            $this->hood(),
            $this->robe(),
            $this->height(),
            $this->weight(),
            $this->hatSize(),
            $this->degreeLevel(),
            $this->degreeField(),
            $this->institutionName(),
            $this->institutionCity(),
            $this->institutionState(),
            $this->colorBand(),
            $this->colorLining(),
            $this->colorChevron()
        );
    }

    public static function assertDataIsValid(
        bool $hat,
        bool $hood,
        bool $robe,
        ?int $size_height,
        ?int $size_weight,
        ?string $size_hat,
        ?string $degree_level,
        ?string $degree_field,
        ?string $inst_name,
        ?string $inst_city,
        ?string $inst_state,
        ?string $color_band,
        ?string $color_lining,
        ?string $color_chevron
    ) {
        if ($hat) {
            if (!$size_hat) throw new \Exception("Hat size is required to order a hat");
        }
        if ($hood) {
            if (!$degree_level) throw new \Exception("Degree level is required to order a hood");
            if (!$degree_field) throw new \Exception("Degree field is required to order a hood");
            if (!$inst_name) throw new \Exception("Institution name is required to order a hood");
            if (!$inst_city) throw new \Exception("Institution city is required to order a hood");
            // NOTE: inst_state is not actually required -- there are institutions without it (mostly foreign ones)
            if (!$color_band) throw new \Exception("Band color is required to order a hood");
            if (!$color_lining) throw new \Exception("Lining color is required to order a hood");
            // NOTE: color_chevron is not actually required -- there are institutions without it
        }
        if ($robe) {
            if (!$degree_level) throw new \Exception("Degree level is required to order a robe");
            if (!$size_height) throw new \Exception("Height is required to order a robe");
            if (!$size_weight) throw new \Exception("Weight is required to order a robe");
        }
    }

    public static function checkDataValidity(
        bool $hat,
        bool $hood,
        bool $robe,
        ?int $size_height,
        ?int $size_weight,
        ?string $size_hat,
        ?string $degree_level,
        ?string $degree_field,
        ?string $inst_name,
        ?string $inst_city,
        ?string $inst_state,
        ?string $color_band,
        ?string $color_lining,
        ?string $color_chevron
    ): bool {
        try {
            static::assertDataIsValid(
                $hat,
                $hood,
                $robe,
                $size_height,
                $size_weight,
                $size_hat,
                $degree_level,
                $degree_field,
                $inst_name,
                $inst_city,
                $inst_state,
                $color_band,
                $color_lining,
                $color_chevron
            );
            return true;
        } catch (\Throwable $th) {
            return false;
        }
    }

    public static function createRaw(
        RegaliaGroup $group,
        string $type,
        ?string $identifier,
        ?string $first_name,
        ?string $last_name,
        ?string $email,
        ?int $size_height,
        ?int $size_weight,
        ?string $size_hat,
        ?string $degree_level,
        ?string $degree_field,
        ?string $inst_name,
        ?string $inst_city,
        ?string $inst_state,
        ?string $color_band,
        ?string $color_lining,
        ?string $color_chevron,
        bool $hat,
        ?bool $tam,
        bool $hood,
        bool $robe,
        array $data
    ): RegaliaOrder {
        if ($group->ordersLocked()) throw new \Exception("Order group is locked and new orders can no longer be created");
        // remove unnecessary data
        if (!$hat) $size_hat = null;
        if (!$robe) {
            $size_height = null;
            $size_weight = null;
        }
        if (!$hood) {
            $degree_field = null;
            $inst_name = null;
            $inst_city = null;
            $inst_state = null;
            $color_band = null;
            $color_lining = null;
            $color_chevron = null;
        }
        if (!$robe && !$hood) {
            $degree_level = null;
            $degree_field = null;
        }
        // dispatch event that can update data array
        $data = [
            'group_id' => $group->id(),
            'type' => $type,
            'identifier' => $identifier,
            'last_name' => $last_name,
            'first_name' => $first_name,
            'email' => $email,
            'size_height' => $size_height,
            'size_weight' => $size_weight,
            'size_hat' => $size_hat,
            'degree_level' => $degree_level,
            'degree_field' => $degree_field,
            'inst_name' => $inst_name,
            'inst_city' => $inst_city,
            'inst_state' => $inst_state,
            'color_band' => $color_band,
            'color_lining' => $color_lining,
            'color_chevron' => $color_chevron,
            'hat' => $hat,
            'tam' => $tam,
            'hood' => $hood,
            'robe' => $robe,
            'cancelled' => false,
            'data' => json_encode($data),
            'created' => time(),
        ];
        Dispatcher::dispatchEvent('onRegaliaOrderCreate', [&$data]);
        // ensure data is all correct
        static::assertDataIsValid(
            $data['hat'],
            $data['hood'],
            $data['robe'],
            $data['size_height'],
            $data['size_weight'],
            $data['size_hat'],
            $data['degree_level'],
            $data['degree_field'],
            $data['inst_name'],
            $data['inst_city'],
            $data['inst_state'],
            $data['color_band'],
            $data['color_lining'],
            $data['color_chevron']
        );
        // insert and return
        return RegaliaOrders::get(
            DB::query()->insertInto(
                'regalia_order',
                $data
            )->execute()
        );
    }
}
