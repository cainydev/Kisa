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
            $this->entity->addMedia($this->document->getRealPath())->toMediaCollection($this->collection);
        }
    }

    public function render()
    {

        phpinfo();
        return view('livewire.doc-uploader');
    }
}
