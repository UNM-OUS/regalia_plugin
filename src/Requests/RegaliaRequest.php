<?php

namespace DigraphCMS_Plugins\unmous\regalia\Requests;

use DigraphCMS\DB\DB;
use DigraphCMS\Events\Dispatcher;
use DigraphCMS_Plugins\unmous\ous_digraph_module\Semester;
use DigraphCMS_Plugins\unmous\regalia\RegaliaOrder;
use DigraphCMS_Plugins\unmous\regalia\RegaliaOrders;
use Flatrr\FlatArray;

class RegaliaRequest
{
    protected $id, $semester, $identifier, $preferred_group, $cancelled, $parent, $assigned_order;

    /** @var string|FlatArray */
    protected $data;
    protected $parent_value = false;

    public static function create(
        Semester $semester,
        string $identifier,
        RegaliaRequestingParent $parent,
        array $data = [],
        RegaliaOrder $order = null
    ): RegaliaRequest {
        return RegaliaRequests::get(
            DB::query()->insertInto(
                'regalia_request',
                [
                    'semester' => $semester->intVal(),
                    'identifier' => $identifier,
                    'cancelled' => false,
                    'parent' => $parent->uuid(),
                    'assigned_order' => $order ? $order->id() : null,
                    'preferred_group' => $parent->regaliaPreferredGroup(),
                    'data' => json_encode($data)
                ]
            )->execute()
        );
    }

    /**
     * When deleting a regalia request, we also cancel any orders associated
     * with it if it was the only request for that order. Cancellation only
     * happens if the group's cancellation is not locked.
     *
     * @return void
     */
    public function delete(): void
    {
        DB::beginTransaction();
        if ($order = $this->order()) {
            if (!$order->group()->cancellationLocked()) {
                $requests = RegaliaRequests::select()->where('assigned_order', $order->id())->count();
                if ($requests <= 1) $order->setCancelled(true);
            }
        }
        DB::query()->delete('regalia_request', $this->id())->execute();
        DB::commit();
    }

    public function save(): bool
    {
        // update data and assigned order
        $update = [
            'data' => json_encode($this->data()->get()),
            'assigned_order' => $this->assigned_order,
            'preferred_group' => $this->parent()->regaliaPreferredGroup(),
            'cancelled' => $this->cancelled()
        ];
        // execute query
        return !!DB::query()->update(
            'regalia_request',
            $update,
            $this->id()
        )->execute();
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

    public function preferredGroup(): ?string
    {
        return $this->preferred_group;
    }

    /**
     * @param RegaliaOrder $order
     * @return static
     */
    public function setOrder(RegaliaOrder $order)
    {
        $this->assigned_order = $order->id();
        return $this;
    }

    public function id(): int
    {
        return $this->id;
    }

    public function semester(): Semester
    {
        return Semester::fromCode($this->semester);
    }

    public function identifier(): string
    {
        return $this->identifier;
    }

    public function cancelled(): bool
    {
        return $this->cancelled;
    }

    public function order(): ?RegaliaOrder
    {
        return RegaliaOrders::get(intval($this->assigned_order));
    }

    public function orderID(): ?int
    {
        return $this->assigned_order;
    }

    public function parent(): RegaliaRequestingParent
    {
        return $this->parent_value === false
            ? $this->parent_value = Dispatcher::firstValue('onRegaliaRequestParent', [$this->parent])
            : $this->parent_value;
    }

    public function data(): FlatArray
    {
        if (is_string($this->data)) $this->data = new FlatArray(json_decode($this->data, true, 512, JSON_THROW_ON_ERROR));
        return $this->data;
    }
}
