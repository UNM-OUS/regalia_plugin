<?php

namespace DigraphCMS_Plugins\unmous\regalia\Requests;

use DigraphCMS\DB\DB;
use DigraphCMS_Plugins\unmous\ous_digraph_module\PersonInfo;
use DigraphCMS_Plugins\unmous\ous_digraph_module\Semester;
use DigraphCMS_Plugins\unmous\regalia\RegaliaGroup;
use DigraphCMS_Plugins\unmous\regalia\RegaliaOrder;

class RegaliaRequester
{
    protected $semester, $identifier, $requests;

    public function __construct(Semester $semester = null, string $identifier = null)
    {
        $this->semester = $this->semester ?? $semester->intVal();
        $this->identifier = $this->identifier ?? $identifier;
    }

    /**
     * Create a new order in the given group for this person, and assign all
     * unassigned requests to it.
     *
     * @param RegaliaGroup $group
     * @return static
     */
    public function createOrderIn(RegaliaGroup $group)
    {
        DB::beginTransaction();
        $this->assignOrder(RegaliaOrder::createForPerson(
            $group,
            $this->identifier(),
            'order',
            $this->prefersTam()
        ));
        DB::commit();
        return $this;
    }

    /**
     * Create a new order in the given group for this person, and assign all
     * unassigned requests to it. Use UNM colors.
     *
     * @param RegaliaGroup $group
     * @return static
     */
    public function createUnmOrderIn(RegaliaGroup $group)
    {
        DB::beginTransaction();
        $this->assignOrder(RegaliaOrder::createUnmForPerson(
            $group,
            $this->identifier(),
            'order',
            $this->prefersTam()
        ));
        DB::commit();
        return $this;
    }

    /**
     * Assign an order to be used as an extra for this requester
     *
     * @param RegaliaOrder $order
     * @return static
     */
    public function assignExtra(RegaliaOrder $order)
    {
        if ($order->type() != 'extra') return $this;
        $person = PersonInfo::fetch($this->identifier());
        $order->setEmail($person['email'])
            ->setFirstName($person->firstName() ?? $this->identifier())
            ->setLastName($person->lastName() ?? $this->identifier())
            ->setIdentifier($this->identifier())
            ->save();
        $this->assignOrder($order);
        return $this;
    }

    /**
     * Assign all unassigned requests to the given order.
     *
     * @param RegaliaOrder $order
     * @return static
     */
    public function assignOrder(RegaliaOrder $order)
    {
        foreach ($this->requests() as $request) {
            if (!$request->orderID()) {
                $request->setOrder($order)->save();
            }
        }
        return $this;
    }

    /**
     * @return RegaliaRequest[]
     */
    public function requests(): array
    {
        return $this->requests ?? $this->requests = RegaliaRequests::select()
            ->where('identifier', $this->identifier)
            ->where('semester', $this->semester)
            ->fetchAll();
    }

    public function identifier(): string
    {
        return $this->identifier;
    }

    public function semester(): Semester
    {
        return Semester::fromCode($this->semester);
    }

    public function prefersTam(): ?bool
    {
        $preferred = null;
        foreach ($this->requests as $request) {
            $pPreferred = $request->parent()->regaliaPrefersTam();
            if ($pPreferred) return true;
            elseif ($pPreferred === false) $preferred = false;
        }
        return $preferred;
    }

    public function preferredGroup(): string
    {
        $platform = false;
        $marshal = false;
        foreach ($this->requests() as $request) {
            if (!$request->preferredGroup()) continue;
            if (str_contains($request->preferredGroup(), 'platform')) $platform = true;
            elseif (str_contains($request->preferredGroup(), 'marshal')) $marshal = true;
        }
        if ($platform) return 'platform';
        elseif ($marshal) return 'marshal';
        else return 'normal';
    }
}
