<?php

namespace DigraphCMS_Plugins\unmous\regalia;

use DigraphCMS\DB\DB;
use DigraphCMS\URL\URL;
use DigraphCMS_Plugins\unmous\ous_digraph_module\Semester;
use DigraphCMS_Plugins\unmous\ous_digraph_module\Semesters;

class RegaliaGroup
{
    protected $id, $name, $type, $tams, $semester;
    /** @var bool */
    protected $lock_orders;
    /** @var bool */
    protected $lock_cancellation;

    public static function create(
        string $name,
        string $type,
        Semester $semester,
        bool $tams
    ): RegaliaGroup {
        return RegaliaGroups::get(
            DB::query()->insertInto(
                'regalia_group',
                [
                    'name' => $name,
                    'type' => $type,
                    'semester' => $semester->intVal(),
                    'tams' => $tams,
                    'lock_orders' => false,
                    'lock_cancellation' => false,
                ]
            )->execute()
        );
    }

    public function delete(): bool
    {
        DB::beginTransaction();
        // unlock this group so we can delete orders
        $this->setLockCancellation(false);
        $this->setLockOrders(false);
        $this->save();
        // delete orders
        $orders = $this->allOrders();
        while ($order = $orders->fetch()) {
            $order->delete();
        }
        // delete and commit
        $out = !!DB::query()
            ->delete('regalia_group', $this->id)
            ->execute();
        DB::commit();
        return $out;
    }

    public function save(): bool
    {
        return !!DB::query()->update(
            'regalia_group',
            [
                'name' => $this->name(),
                'type' => $this->type(),
                'semester' => $this->semester()->intVal(),
                'tams' => $this->tams(),
                'lock_orders' => $this->ordersLocked(),
                'lock_cancellation' => $this->cancellationLocked(),
            ],
            $this->id()
        )->execute();
    }

    /**
     * @param string $name
     * @return static
     */
    public function setName(string $name)
    {
        $this->name = $name;
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
     * @param Semester $semester
     * @return static
     */
    public function setSemester(Semester $semester)
    {
        $this->semester = $semester;
        return $this;
    }

    /**
     * @param bool $tams
     * @return static
     */
    public function setTams(bool $tams)
    {
        $this->tams = $tams;
        return $this;
    }

    /**
     * @param bool $lock_orders
     * @return static
     */
    public function setLockOrders(bool $lock_orders)
    {
        $this->lock_orders = $lock_orders;
        return $this;
    }

    /**
     * @param bool $lock_cancellation
     * @return static
     */
    public function setLockCancellation(bool $lock_cancellation)
    {
        $this->lock_cancellation = $lock_cancellation;
        return $this;
    }

    public function url(): URL
    {
        return (new URL('/regalia/group:' . $this->id()))
            ->setName($this->name());
    }

    public function orders(): RegaliaOrderSelect
    {
        return RegaliaOrders::select()
            ->where('group_id', $this->id())
            ->where('regalia_order.type <> ?', ['extra'])
            ->order('last_name ASC, first_name ASC');
    }

    public function allOrders(): RegaliaOrderSelect
    {
        return RegaliaOrders::select()
            ->where('group_id', $this->id());
    }

    public function extras(): RegaliaOrderSelect
    {
        return RegaliaOrders::select()
            ->where('group_id', $this->id())
            ->where('regalia_order.type', 'extra')
            ->order('CASE WHEN identifier is null THEN 1 ELSE 0 END')
            ->order('CASE WHEN email is null THEN 1 ELSE 0 END')
            ->order('CASE WHEN last_name is null THEN 1 ELSE 0 END')
            ->order('CASE WHEN first_name is null THEN 1 ELSE 0 END')
            ->order('last_name ASC, first_name ASC') // sort names to top
            ->order('robe DESC, hood DESC') // sort those without robe/hood to top
            ->order('size_height ASC')
            ->order('CASE WHEN size_hat = "XS" THEN 1 WHEN size_hat = "S" THEN 2 WHEN size_hat = "M" THEN 3 WHEN size_hat = "L" THEN 4 WHEN size_hat = "XL" THEN 5 ELSE 0 END'); // sort by hat size
    }

    public function id(): int
    {
        return $this->id;
    }

    public function name(): string
    {
        return $this->name;
    }

    /**
     * @return "normal"|"extra"|"platform"
     */
    public function type(): string
    {
        return $this->type;
    }

    public function semester(): Semester
    {
        return Semesters::fromCode($this->semester);
    }

    public function ordersLocked(): bool
    {
        return $this->lock_orders;
    }

    public function cancellationLocked(): bool
    {
        return $this->lock_cancellation;
    }

    public function tams(): ?bool
    {
        return $this->tams;
    }
}
