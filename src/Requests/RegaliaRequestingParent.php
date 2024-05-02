<?php

namespace DigraphCMS_Plugins\unmous\regalia\Requests;

use DigraphCMS\URL\URL;

interface RegaliaRequestingParent
{
    public function uuid(): string;
    public function regaliaPrefersTam(): ?bool;
    public function regaliaOrderType(): string;
    public function regaliaPreferredGroup(): ?string;
    public function requestRegaliaCancellation();
    public function requestRegaliaUncancellation();
    public function regaliaRequestPriority(): int;
    public function url(): URL;
    public function name(): string;
}
