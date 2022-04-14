<?php

namespace App\Orchid\Layouts\Inspectors;

use Orchid\Screen\Layouts\Table;
use App\Orchid\Fields\Group;
use Orchid\Screen\Actions\Link;
use Orchid\Screen\Actions\ModalToggle;
use Orchid\Screen\TD;


class InspectorListLayout extends Table
{
    /**
     * Data source.
     *
     * The name of the key to fetch it from the query.
     * The results of which will be elements of the table.
     *
     * @var string
     */
    protected $target = 'inspectors';

    /**
     * Get the table cells to be displayed.
     *
     * @return TD[]
     */
    protected function columns(): iterable
    {
        return [
            TD::make('id', 'ID'),
            TD::make('company', 'Firma'),
            TD::make('label', 'Kennzeichnung'),
            TD::make()
                ->align(TD::ALIGN_RIGHT)
                ->render(function ($inspector) {
                    return Group::make([
                        ModalToggle::make()
                            ->modal('deleteInspector')
                            ->class('btn btn-danger p-2')
                            ->method('deleteInspector')
                            ->parameters(['inspector-id' => $inspector->id])
                            ->icon('trash'),
                        Link::make()
                            ->icon('pencil')
                            ->class('btn btn-primary p-2')
                            ->route('platform.meta.inspector.edit', ['inspector' => $inspector]),
                    ]);
                })
        ];
    }
}
