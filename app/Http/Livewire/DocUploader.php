<?php

namespace App\Http\Livewire;

use Livewire\Component;
use Livewire\WithFileUploads;

class DocUploader extends Component
{
    use WithFileUploads;

    public $entity;
    public $collection;
    public $title;
    public $document;

    protected $rules = [
        'document' => 'file|mimetypes:application/pdf'
    ];

    public function updatedDocument(){
        $this->validate();

        if($this->entity != null) {
            $this->entity
            ->addMedia($this->document->getRealPath())
            ->withCustomProperties(['name' => $this->document->getClientOriginalName()])
            ->toMediaCollection($this->collection);
        }
    }

    public function download(){
        $media = $this->entity->getFirstMedia($this->collection);
        $name = $media->hasCustomProperty('name') ? $media->getCustomProperty('name') : $this->title;
        return response()->download($media->getPath(), $name);
    }

    public function delete(){
        $this->entity->clearMediaCollection($this->collection);
    }

    public function render()
    {
        $this->entity = ($this->entity::class)::find($this->entity->id);
        return view('livewire.doc-uploader');
    }
}
