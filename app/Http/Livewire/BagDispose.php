<?php

namespace App\Http\Livewire;

use Illuminate\Support\Facades\Log;
use Livewire\Component;

use App\Models\Bag;

class BagDispose extends Component
{
    protected function rules() {
        return [
            'bag.trashed' => 'required|numeric|min:0|max:'.$this->bag->getCurrent()
        ];
    }

    public Bag $bag;

    public function mount(Bag $bag){
        $this->bag = $bag;

        if($this->bag->getCurrentWithTrashed() < 0){
            if($this->bag->getCurrent() < 0){
                Log::error('Bags current usage higher than its size!', ['bag' => $this->bag]);
            }else{
                $this->bag->trashed = $this->bag->getCurrent();
                $this->save();
            }
        }
    }

    public function all(){
        $this->bag->trashed = $this->bag->getCurrent();
        $this->save();
    }

    public function save(){
        if($this->bag->trashed == '') $this->bag->trashed = 0;
        $this->validate();
        $this->bag->save();
    }

    public function updated(){
        $this->save();
    }

    public function render()
    {
        $this->bag = Bag::find($this->bag->id);
        return view('livewire.bag-dispose');
    }
}
