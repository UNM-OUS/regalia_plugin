<?php

namespace DigraphCMS_Plugins\unmous\regalia;

use DigraphCMS\Spreadsheets\CellWriters\LinkCell;
use DigraphCMS\UI\Pagination\ColumnSortingHeader;
use DigraphCMS\UI\Pagination\ColumnStringFilteringHeader;
use DigraphCMS\UI\Pagination\PaginatedTable;

class RegaliaOrderTable extends PaginatedTable
{
    const JOSTENS_PARTS = [
        'HOOD',
        'PACKAGE T/G',
        'PACKAGE C/G',
        'TAM',
        'CAP',
        'GOWN',
    ];

    public function __construct(RegaliaOrderSelect $select, string $download = null)
    {
        parent::__construct($select, [$this, 'regaliaCallback'], $this->regaliaHeaders());
        if ($download) {
            $this->download(
                $download,
                [$this, 'regaliaDownloadCallback'],
                $this->regaliaDownloadHeaders(),
                null,
                null,
                60 // ttl
            );
        }
    }

    public function regaliaHeaders(): array
    {
        return [
            'Order',
            new ColumnStringFilteringHeader('Last name', 'last_name'),
            new ColumnStringFilteringHeader('First name', 'first_Name'),
            new ColumnStringFilteringHeader('Email', 'email'),
            'Hat',
            'Hood',
            'Robe',
            new ColumnSortingHeader('Size', 'size_height'),
            'Level',
        ];
    }

    public function regaliaCallback(RegaliaOrder $order): array
    {
        return [
            sprintf('<a href="%s">%s</a>', $order->url(), $order->orderName()),
            $order->lastName(),
            $order->firstName(),
            $order->email() ? sprintf('<a href="mailto:%s">%s</a>', $order->email(), $order->email()) : '',
            $order->hat() ? ($order->tam() ? 'Tam (' . $order->hatSize() . ')' : 'Cap') : '',
            $order->hood() ? 'Hood' : '',
            $order->robe() ? 'Robe' : '',
            implode(', ', array_filter([
                $order->heightHR(),
                $order->weight()
            ])),
            $order->degreeLevel(),
        ];
    }

    public function regaliaDownloadHeaders(): array
    {
        $headers = [
            'Order',
            'Last Name',
            'First Name',
            'Email',
            'Height',
            'Weight',
            'Hat Size',
            'Degree',
            'Field',
            'School',
            'City',
            'State',
            'Band Color',
            'Lining Color 1',
            'Chevron Color 1',
        ];
        $headers = array_merge($headers, self::JOSTENS_PARTS);
        return $headers;
    }

    public function regaliaDownloadCallback(RegaliaOrder $order): array
    {
        $row = [
            new LinkCell($order->orderName(), $order->url()),
            $order->lastName(),
            $order->firstName(),
            $order->email(),
            $order->heightHR(),
            $order->weight(),
            $order->tam() ? $order->hatSize() : 'ELASTIC',
            $order->degreeLevel(),
            $order->degreeField(),
            $order->institutionName(),
            $order->institutionCity(),
            $order->institutionState(),
            $order->colorBand(),
            $order->colorLining(),
            $order->colorChevron(),
        ];
        $jostens_parts = $order->order_jostens();
        foreach (self::JOSTENS_PARTS as $part) {
            $row[] = in_array($part, $jostens_parts) ? $part : '';
        }
        return $row;
    }
}
